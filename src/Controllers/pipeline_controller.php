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

namespace Emeraldion\Magritte\Controllers;

require_once __DIR__ . '/../models/pipeline_item.php';

use Emeraldion\EmeRails\Helpers\Request;

use Emeraldion\Magritte\Helpers\PipelineRunner;
use Emeraldion\Magritte\Models\Pipeline;
use Emeraldion\Magritte\Models\PipelineStage;

/**
 * @class PipelineController
 * @short Edit this controller's short description
 * @details Edit this controller's detailed description
 */
class PipelineController extends MagritteController
{
    const ITEM_CLASS = PipelineItem::class;

    /**
     * @fn init
     * @short Performs specialized initialization
     * @details You should use this method to do your custom initialization.
     */
    protected function init()
    {
        parent::init();

        $this->allow_method(Request::METHOD_POST, [
            'promote_stage',
            'purge_stage',
            'run',
            'save_layout',
            'set_enabled',
            'stage_promotion_enable'
        ]);
        $this->allow_method(Request::METHOD_GET, [
            'except' => ['promote_stage', 'purge_stage', 'run', 'save_layout', 'set_enabled', 'stage_promotion_enable']
        ]);

        $this->accept_parameter(
            [
                'items',
                'promote_stage',
                'purge_stage',
                'run',
                'save_layout',
                'set_enabled',
                'stage',
                'stage_promotion_enable',
                'view'
            ],
            'id',
            [
                'type' => 'int',
                'required' => true
            ]
        );
        $this->accept_parameter(['stage_promotion_enable'], 'enabled', [
            'type' => 'bool',
            'default' => true
        ]);
        $this->accept_parameter(['items'], 'stage', [
            'type' => 'string[]'
        ]);
        $this->accept_parameter(['run'], 'stage', [
            'type' => 'string'
        ]);
        $this->accept_parameter(['set_enabled'], 'enabled', [
            'type' => 'bool'
        ]);
        $this->accept_parameter(['run'], 'verbose', [
            'type' => 'bool',
            'default' => true
        ]);
        $this->accept_parameter(['run'], 'dry-run', [
            'type' => 'bool',
            'default' => false
        ]);
        $this->accept_parameter(['run'], 'expunge', [
            'type' => 'bool',
            'default' => false
        ]);
        $this->accept_parameter(['run'], 'limit', [
            'type' => 'int',
            'default' => 10
        ]);
        $this->accept_parameter(['run'], 'start', [
            'type' => 'int',
            'default' => 0
        ]);
    }

    /**
     * @fn index
     * @short This is the default action
     * @details This is the default action when the controller is invoked without an action
     */
    public function index()
    {
        $factory = new Pipeline();
        $this->pipelines = $factory->find_all();
        $this->set_title(l('pipeline-index-title'));
    }

    /**
     * @fn view
     * @short Edit this actions's short description
     * @details Edit this actions's detailed description
     */
    public function view()
    {
        if (!($pipeline = $this->pipeline = Pipeline::find($this->parameters->id))) {
            $this->send_error(404);
        }
        $this->stages = [];
        try {
            if ($pipeline->has_many(PipelineStage::class, ['as' => 'stages'])) {
                $this->stages = array_values($this->pipeline->stages);
            }
        } catch (Throwable $t) {
            $this->flash(sprintf(l('pipeline-view-error-@1'), $t->getMessage()), 'error');
        }
        $this->pipeline->has_and_belongs_to_many(get_called_class()::ITEM_CLASS, [
            'as' => 'items' // 'order_by' => '`last_run_at` ASC'
        ]);

        $this->status = sprintf(Config::get('CLI_BANNER'), l('pipeline-view-welcome-message'));

        $this->set_title(sprintf(l('pipeline-view-title-@1'), $this->pipeline->name));
    }

    /**
     * @fn set_enabled
     * @short Edit this actions's short description
     * @details Edit this actions's detailed description
     */
    public function set_enabled()
    {
        $this->mimetype = 'application/json';
        try {
            if (!($pipeline = Pipeline::find($this->parameters->id))) {
                throw new Exception(l('pipeline-set-enabled-no-such-pipeline-error'));
            }
            $pipeline->enabled = $this->parameters->enabled ? 1 : 0;
            $pipeline->save();
            print json_encode([
                'success' => true,
                'enabled' => $pipeline->enabled ? true : false
            ]);
        } catch (Throwable $t) {
            print json_encode([
                'success' => false,
                'error' => $t->getMessage()
            ]);
        } catch (Error $e) {
            print json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        $this->render(null);
    }

    /**
     * @fn stage
     * @short Edit this actions's short description
     * @details Edit this actions's detailed description
     */
    public function stage()
    {
        if ($stage = $this->stage = PipelineStage::find($this->parameters->id)) {
            // var_dump($stage);

            if ($stage->belongs_to(Pipeline::class)) {
                // var_dump($pipeline->pipeline_stages);
            }
        }

        $this->set_title(sprintf(l('pipeline-stage-title-@1'), $this->stage->name));
    }

    /**
     * @fn promote_stage
     * @short Edit this actions's short description
     * @details Edit this actions's detailed description
     */
    public function promote_stage()
    {
        $this->mimetype = 'application/json';
        try {
            if (!($stage = PipelineStage::find($this->parameters->id))) {
                throw new Exception(l('pipeline-promote-stage-no-such-stage-error'));
            }
            if (!$stage->belongs_to(Pipeline::class)) {
                throw new Exception(l('pipeline-promote-stage-orphaned-stage-error'));
            }
            if (!($next_stage = PipelineStage::find($stage->next_stage_id))) {
                throw new Exception(l('pipeline-promote-stage-terminal-stage-error'));
            }
            if ($next_stage->pipeline_id != $stage->pipeline->id) {
                throw new Exception(l('pipeline-promote-stage-pipeline-mismatch-error'));
            }
            $conn = $this->get_connection();
            $success = false;
            if (
                $rel = $stage->pipeline->has_and_belongs_to_many(get_called_class()::ITEM_CLASS, [
                    'where_clause' => "`stage` = '{$conn->escape($stage->short_name)}'"
                ])
            ) {
                $success = true;
                foreach ($rel[$stage->pipeline->id] as $isin => $r) {
                    $r->stage = $next_stage->short_name;
                    $success = $success && $r->save();
                }
            }
            print json_encode([
                'success' => $success
            ]);
        } catch (Throwable $t) {
            print json_encode([
                'error' => $t->getMessage()
            ]);
        }
        $this->render(null);
    }

    /**
     * @fn purge_stage
     * @short Purges all items in the stage
     * @details Edit this actions's detailed description
     */
    public function purge_stage()
    {
        $this->mimetype = 'application/json';
        try {
            if (!($stage = PipelineStage::find($this->parameters->id))) {
                throw new Exception(l('pipeline-purge-stage-no-such-stage-error'));
            }
            if (!$stage->belongs_to(Pipeline::class)) {
                throw new Exception(l('pipeline-purge-stage-orphaned-stage-error'));
            }
            $conn = $this->get_connection();
            $success = false;
            if (
                $rel = $stage->pipeline->has_and_belongs_to_many(get_called_class()::ITEM_CLASS, [
                    'where_clause' => "`stage` = '{$conn->escape($stage->short_name)}'"
                ])
            ) {
                $success = true;
                foreach ($rel[$stage->pipeline->id] as $isin => $r) {
                    $success = $success && $r->delete();
                }
            }
            print json_encode([
                'success' => $success
            ]);
        } catch (Throwable $t) {
            print json_encode([
                'error' => $t->getMessage()
            ]);
        }
        $this->render(null);
    }

    /**
     * @fn stage_promotion_enable
     * @short Edit this actions's short description
     * @details Edit this actions's detailed description
     */
    public function stage_promotion_enable()
    {
        $this->mimetype = 'application/json';
        try {
            if (!($stage = PipelineStage::find($this->parameters->id))) {
                throw new Exception(l('pipeline-stage-promotion-enable-no-such-stage-error'));
            }
            if (!$stage->belongs_to(Pipeline::class)) {
                throw new Exception(l('pipeline-stage-promotion-enable-orphaned-stage-error'));
            }
            $stage->promotion_enabled = $this->parameters->enabled ? 1 : 0;
            $stage->save();
            print json_encode([
                'success' => true,
                'enabled' => $stage->promotion_enabled ? true : false
            ]);
        } catch (Throwable $t) {
            print json_encode([
                'success' => false,
                'error' => $t->getMessage()
            ]);
        } catch (Error $e) {
            print json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        $this->render(null);
    }

    /**
     * @fn run
     * @short Edit this actions's short description
     * @details Edit this actions's detailed description
     */
    public function run()
    {
        $this->mimetype = 'application/json';
        try {
            if (!($this->pipeline = Pipeline::find($this->parameters->id))) {
                throw new Exception(l('pipeline-run-no-such-pipeline-error'));
            }
            $conn = $this->get_connection();
            if (
                !$this->pipeline->has_many(PipelineStage::class, [
                    'where_clause' => "`short_name` = '{$conn->escape($this->parameters->stage)}'",
                    'as' => 'stages'
                ])
            ) {
                throw new Exception(l('pipeline-run-pipeline-has-no-stages-error'));
            }
            $runner = PipelineRunner::for(get_called_class()::ITEM_CLASS);
            $user_args = [];
            if ($extra_fields = $this->request->get_parameter('extra-fields')) {
                foreach (explode(',', $extra_fields) as $field_name) {
                    $user_args[$field_name] = $this->request->get_parameter($field_name);
                }
            }

            ob_start();
            $runner->run(
                $this->pipeline->short_name,
                $this->parameters->stage,
                $this->parameters->verbose,
                // FIXME: kebab case URL params should become snake case properties
                // $this->accept_parameter(..., 'dry-run', [...]);
                // $this->parmeters->dry_run;
                $this->request->get_parameter('dry-run'),
                $this->parameters->limit,
                $this->parameters->start,
                $this->parameters->expunge,
                $user_args
            );
            $log = ob_get_clean();
            print json_encode([
                'success' => true,
                'log' => $log
            ]);
        } catch (Throwable $t) {
            print json_encode([
                'success' => false,
                'error' => $t->getMessage()
            ]);
        } catch (Error $e) {
            print json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        $this->render(null);
    }

    /**
     * @fn items
     * @short Edit this actions's short description
     * @details Edit this actions's detailed description
     */
    public function items()
    {
        $this->mimetype = 'application/json';
        try {
            if (!($this->pipeline = Pipeline::find($this->parameters->id))) {
                throw new Exception(l('pipeline-items-no-such-pipeline-error'));
            }

            $items = [];
            if ($this->pipeline->has_many(PipelineStage::class, ['as' => 'stages'])) {
                $conn = $this->get_connection();
                $values = implode(
                    ',',
                    array_map(function ($stage) use ($conn) {
                        return "'" . $conn->escape($stage) . "'";
                    }, $this->parameters->stage)
                );
                if (
                    $this->pipeline->has_and_belongs_to_many(get_called_class()::ITEM_CLASS, [
                        'as' => 'items',
                        'where_clause' => "`stage` IN ({$values})"
                        // 'order_by' => '`last_run_at` ASC'
                    ])
                ) {
                    $items_grouped_by_stage = group_by(function ($item) {
                        return $item->stage;
                    }, $this->pipeline->items);
                    $identifiers_grouped_by_stage = [];
                    foreach ($items_grouped_by_stage as $stage => $items) {
                        $identifiers_grouped_by_stage[$stage] = array_map(function ($item) {
                            return ['identifier' => $item->get_identifier(), 'label' => $item->get_label()];
                        }, $items);
                    }
                    // Fill missing stages with empty arrays
                    foreach ($this->pipeline->stages as $stage) {
                        if (!array_key_exists($stage->short_name, $identifiers_grouped_by_stage)) {
                            $identifiers_grouped_by_stage[$stage->short_name] = [];
                        }
                    }
                    $items = $identifiers_grouped_by_stage;
                }
            }
            print json_encode([
                'success' => true,
                'pipeline' => [
                    'id' => $this->pipeline->id,
                    'stages' => array_map(function ($stage) {
                        return ['id' => $stage->id, 'name' => $stage->short_name];
                    }, $this->pipeline->stages ?? [])
                ],
                'items' => $items
            ]);
        } catch (Throwable $t) {
            print json_encode([
                'success' => false,
                'error' => $t->getMessage()
            ]);
        }

        $this->render(null);
    }

    /**
     * @fn stage_connector
     * @short Edit this actions's short description
     * @details Edit this actions's detailed description
     */
    public function stage_connector()
    {
        // TODO: add your code here
    }

    /**
     * @fn stage_run_button
     * @short Edit this actions's short description
     * @details Edit this actions's detailed description
     */
    public function stage_run_button()
    {
        // TODO: add your code here
    }

    /**
     * @fn save_layout
     * @short Edit this actions's short description
     * @details Edit this actions's detailed description
     */
    public function save_layout()
    {
        $this->mimetype = 'application/json';
        try {
            if (!($pipeline = Pipeline::find($this->parameters->id))) {
                throw new Exception(l('pipeline-save-layout-no-such-pipeline-error'));
            }
            if ($pipeline->has_many(PipelineStage::class, ['as' => 'stages'])) {
                $layout = json_decode($_POST['layout'], true);
                foreach ($pipeline->stages as $stage) {
                    $stage->layout = json_encode($layout[$stage->short_name]);
                    $stage->save();
                }
            }
            print json_encode([
                'success' => true,
                'layout' => $layout
            ]);
        } catch (Throwable $t) {
            print json_encode([
                'success' => false,
                'error' => $t->getMessage()
            ]);
        } catch (Error $e) {
            print json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        $this->render(null);
    }
}
