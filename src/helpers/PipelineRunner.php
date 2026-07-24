<?php
/**
 *                                _ __  __
 *    ____ ___  ____ _____ ______(_) /_/ /____
 *   / __ `__ \/ __ `/ __ `/ ___/ / __/ __/ _ \
 *  / / / / / / /_/ / /_/ / /  / / /_/ /_/  __/
 * /_/ /_/ /_/\__,_/\__, /_/  /_/\__/\__/\___/
 *                 /____/
 *
 * (c) Claudio Procida 2026
 *
 * @format
 */

namespace Emeraldion\Magritte\Helpers;

require_once __DIR__ . '/../../models/pipeline_item.php';

use Emeraldion\EmeRails\Db;

use Emeraldion\Magritte\Models\Pipeline;
use Emeraldion\Magritte\Models\PipelineStage;

class PipelineRunner
{
    private $itemclass;

    protected function __construct(string $classname)
    {
        $this->itemclass = $classname;
    }

    public static function for(string $classname): self
    {
        return new self($classname);
    }

    /**
     * @fn run
     * @short Runs a pipeline stage
     * @details This method runs a pipeline stage against a selection of the associated items, controlled by the arguments.
     * The items are promoted to the next stage, unless the stage is the last. In that case, the items are left in place,
     * unless the <tt>expunge</tt> argument is set to true, in which case they're expunged i.e. removed from the pipeline.
     * @param pipeline_name The short name of the pipeline to run
     * @param pipeline_stage_name The short name of the pipeline stage to run
     * @param verbose If true, produces extra diagnostic messages to the output
     * @param dry_run If true, runs the pipeline stage in dry-run mode, i.e. it does not save anything
     * @param limit The number of items to process as a batch
     * @param start The index of the first item to process
     * @param expunge If set to true, the items will be expunged, i.e. dropped from the pipeline
     * @param user_args An optional array containing extra agent arguments provided by the user via form
     */
    public function run(
        $pipeline_name,
        $pipeline_stage_name = null,
        $verbose = false,
        $dry_run = false,
        $limit = 10,
        $start = 0,
        $expunge = false,
        $user_args = []
    ): bool {
        $did_work = false;

        $scheduler = new TaskRunner();
        $conn = Db::get_connection();

        if (!($pipeline = Pipeline::find_by_short_name($pipeline_name))) {
            throw new \Exception(sprintf('Unknown pipeline: %s', $pipeline_name));
        }
        if (!$pipeline->enabled) {
            throw new \Exception(sprintf('Pipeline is disabled: %s', $pipeline_name));
        }

        if ($verbose) {
            printf("Running pipeline: %s\n", $pipeline->short_name);
        }

        $pipeline->has_many(PipelineStage::class, ['as' => 'stages']);

        $items_by_stage = [];
        if (
            $rel = $pipeline->has_and_belongs_to_many($this->itemclass, [
                'as' => 'items',
                'where_clause' => $pipeline_stage_name ? "`stage` = '{$conn->escape($pipeline_stage_name)}'" : '1',
                'order_by' => '`last_run_at` ASC',
                'limit' => $limit,
                'start' => $start
            ])
        ) {
            foreach ($pipeline->items as $item) {
                $items_by_stage[$item->stage][] = $item;
            }
        }

        Db::close_connection($conn);

        foreach ($pipeline->stages as $stage) {
            $stage_short_name = $stage->short_name;
            if ($pipeline_stage_name && $pipeline_stage_name != $stage_short_name) {
                continue;
            }

            $items = array_key_exists($stage_short_name, $items_by_stage) ? $items_by_stage[$stage_short_name] : [];
            if ($items || $stage->runs_empty) {
                if ($verbose) {
                    printf("  Running pipeline stage: %s:%s\n", $pipeline->short_name, $stage_short_name);
                }

                $id = array_map(function ($item) {
                    return $item->id;
                }, $items);

                sort($id);

                // Sets common request variables for task runner
                $_REQUEST['id'] = implode(',', $id);
                // FIXME: are these still needed?
                $_REQUEST['verbose'] = $verbose;
                $_REQUEST['dry-run'] = $dry_run;
                $_REQUEST['__return'] = true;

                $this->add_extra_options_to_request($stage->task, $stage->task_args, $user_args);

                $pipelines_to_items = Relationship::many_to_many(Pipeline::class, $this->itemclass);

                if ($result = $task_runner->run(null, $stage->task)) {
                    $next_stage = null;
                    if ($expunge) {
                        printf("    Items will be expunged from the pipeline as requested\n");
                    } elseif (
                        $stage->next_stage_id &&
                        // FIXME: we treat null as an implicit promotion enablement
                        (is_null($stage->promotion_enabled) || $stage->promotion_enabled)
                    ) {
                        if (!($next_stage = PipelineStage::find($stage->next_stage_id))) {
                            printf("    %s: Cannot find next stage (id: %d)\n", $id, $stage->next_stage_id);
                        } elseif ($next_stage->pipeline_id != $pipeline->id) {
                            printf(
                                "    %s: Next stage (id: %d) belongs to an unexpected pipeline (id: %d)\n",
                                $id,
                                $next_stage->id,
                                $next_stage->pipeline_id
                            );
                        } else {
                            $next_stage_name = $next_stage->short_name;
                        }
                    } else {
                        // Keep the item in the same stage
                        $next_stage_name = $stage_short_name;
                    }

                    $did_work = $did_work || count($result) > 0;

                    // TODO: move this logic to a pipeline post-flight hook
                    foreach (array_keys($result) as $id) {
                        if (!($item = $this->itemclass::find($id))) {
                            continue;
                        }

                        $r = null;

                        if ($rel && array_key_exists($id, $rel[$pipeline->id])) {
                            $r = $rel[$pipeline->id][$id];
                        } else {
                            if (
                                $existing_rel = $pipeline->has_and_belongs_to_many($this->itemclass, [
                                    'as' => '_for_resolution',
                                    'where_clause' => "`{$pipelines_to_items->get_table_name()}`.`id` = '{$conn->escape(
                                        $id
                                    )}'"
                                ])
                            ) {
                                $r = $existing_rel[$pipeline->id][$id];
                            } elseif (!$expunge) {
                                $synt_rel = $pipelines_to_items->among([$pipeline], [$item]);
                                $r = $synt_rel[$pipeline->id][$id];
                                // FIXME: this ISIN should not be in the same pipeline under a different stage;
                                // if so, we should raise this as an error. In the meantime, just get along...
                                $r->_ignore = true;
                            }
                        }

                        if ($r) {
                            if ($expunge) {
                                $r->delete();
                            } else {
                                $r->stage = $next_stage_name;
                                $r->last_run_at = date(TimeFormat::TIMESTAMP);
                                $r->save();
                            }
                        }
                    }
                }
            }
        }

        return $did_work;
    }

    public function inspect($pipeline_name, $pipeline_stage_name = null, $verbose = false, $show_empty = false): void
    {
        $conn = Db::get_connection();

        if (!($pipeline = Pipeline::find_by_short_name($pipeline_name))) {
            throw new \Exception(sprintf('Unknown pipeline: %s', $pipeline_name));
        }

        if ($verbose) {
            printf("Pipeline: %s\n", $pipeline->name);
        }

        $pipeline->has_many(PipelineStage::class, ['as' => 'stages']);

        $items_by_stage = [];
        if (
            $rel = $pipeline->has_and_belongs_to_many($this->itemclass, [
                'as' => 'items',
                'where_clause' => $pipeline_stage_name ? "`stage` = '{$conn->escape($pipeline_stage_name)}'" : '1'
            ])
        ) {
            foreach ($pipeline->items as $item) {
                $items_by_stage[$item->stage][] = $item;
            }
        }

        Db::close_connection($conn);

        foreach ($pipeline->stages as $stage) {
            if ($pipeline_stage_name && $pipeline_stage_name != $stage->short_name) {
                continue;
            }
            $items = array_key_exists($stage->short_name, $items_by_stage) ? $items_by_stage[$stage->short_name] : [];
            if ($items || $show_empty) {
                printf("\n  Stage: %s:%s\n", $pipeline->name, $stage->short_name);
            }

            if ($items) {
                usort($items, function ($item, $other_item) {
                    return strcasecmp($item->name, $other_item->name);
                });

                foreach ($items as $item) {
                    printf("    %s: %s\n", $item->id, $item->name);
                }
            } elseif ($show_empty) {
                print "    <empty>\n";
            }
        }
    }

    public function inject(
        $pipeline_name,
        $pipeline_stage_name = null,
        ?array $identifiers = null,
        $verbose = false
    ): void {
        $conn = Db::get_connection();

        if (!($pipeline = Pipeline::find_by_short_name($pipeline_name))) {
            throw new \Exception(sprintf('Unknown pipeline: %s', $pipeline_name));
        }
        if (!($pipeline_stage = PipelineStage::find_by_short_name($pipeline_stage_name))) {
            throw new \Exception(sprintf('Unknown pipeline stage: %s', $pipeline_stage_name));
        }
        if ($pipeline_stage->pipeline_id != $pipeline->id) {
            throw new \Exception(
                sprintf(
                    'Pipeline stage %s does not belong to pipeline: %s',
                    $pipeline_stage->get_localized_name(),
                    $pipeline->get_localized_name()
                )
            );
        }
        $factory = new $this->itemclass();
        $identifiers_as_list = implode(
            ',',
            array_map(function ($identifier) use ($conn) {
                return $conn->escape($identifier);
            }, $identifiers)
        );
        if (
            !($items = $factory->find_all([
                'where_clause' => "`id` IN ({$identifiers_as_list})"
            ]))
        ) {
            throw new \Exception(sprintf('Unknown items: %s', implode(', ', $identifiers)));
        }

        if ($ret = $pipeline->set_has_and_belongs_to_many($items, ['stage' => $pipeline_stage->short_name])) {
            if ($verbose) {
                foreach ($ret as $item_id => $result) {
                    ['status' => $status, 'success' => $success] = $result;
                    switch ($status) {
                        case 'created':
                            printf("Item %d was injected into stage %s\n", $item_id, $pipeline_stage_name);
                            break;
                        case 'updated':
                            printf("Item %d was moved to stage %s\n", $item_id, $pipeline_stage_name);
                            break;
                    }
                }
            }
        } else {
            print "No changes\n";
        }

        Db::close_connection($conn);
    }

    protected function add_extra_options_to_request(string $task_name, ?string $task_args, array $user_args): void
    {
        if ($task = TaskRegistry::get($task_name)) {
            $fields = [];
            foreach ($task->get_fields() as $field) {
                $fields[$field['name']] = $field;
            }

            // Set options from the user first
            foreach ($user_args as $option_name => $value) {
                if (array_key_exists($option_name, $fields)) {
                    if ($fields[$option_name]['type'] == 'boolean') {
                        $_REQUEST[$option_name] = true;
                    } else {
                        $_REQUEST[$option_name] = $value;
                    }
                }
            }

            // Special option: request the tasks to always return a list of item identifiers
            $_REQUEST['__return'] = true;

            // TODO: refactor using Scheduler::get_request_params_from_args
            if ($stage_args) {
                $args = explode(' ', $stage_args);
                $option_name = null;
                for ($i = 0; $i < count($args); $i += 1) {
                    $arg = $args[$i];
                    if (strpos($arg, '--') === 0) {
                        // Option name, long form
                        $option_name = substr($arg, 2);
                        if (array_key_exists($option_name, $fields)) {
                            if ($fields[$option_name]['type'] == 'boolean') {
                                $_REQUEST[$option_name] = true;
                                unset($option_name);
                            }
                        }
                    } elseif (strpos($arg, '-') === 0) {
                        // Option name, short form
                        // TODO:
                    } else {
                        // Option value
                        $_REQUEST[$option_name] = $arg;
                        unset($option_name);
                    }
                }
            }
        }
    }
}
