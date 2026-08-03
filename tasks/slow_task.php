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

use Emeraldion\Magritte\Helpers\BaseTask;
use Emeraldion\Magritte\Helpers\TaskRegistry;
use Emeraldion\Magritte\Models\PipelineItem;

class SlowTask extends BaseTask
{
    public $name = 'slow';

    public function run($context)
    {
        $verbose = $this->get_option('verbose');
        if ($dry_run = $this->get_option('dry-run')) {
            printf("Note: %s is operating in dry-run mode\n", get_called_class());
        }
        $limit = $this->get_option('limit', 10);
        $sleep_interval = $this->get_option('sleep-interval') ?: 10;

        // $this->log("%s::run\n", get_called_class());

        $result = [];
        if (
            $items = $this->get_items($context, PipelineItem::class, function ($x) {
                return true;
            })
        ) {
            printf("%s found %d items\n", get_called_class(), count($items));

            foreach ($items as $item) {
                printf("* %s\n", $item->get_label());
                sleep($sleep_interval);
                $result[$item->get_identifier()] = true;
            }
        }

        return $this->returns_value() ? $result : !$dry_run;
    }
}

TaskRegistry::register(new SlowTask());
