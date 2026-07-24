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

require_once __DIR__ . '/../config/task_runner.conf.php';

use Emeraldion\EmeRails\Config;

ini_set('max_execution_time', 3000);

const LIMIT = 1e5;

class TaskRunnerContext
{
    public $task_name = null;
    public $level = null;
    public $preferences = null;
    public $stocks = [];
}

class TaskRunner
{
    public function run($level = null, $task_name = null)
    {
        if (!Config::get('TASK_RUNNER_ENABLED')) {
            printf("TaskRunner is not enabled\n");
            return;
        }

        $tags = ['level' => $level, 'task_name' => $task_name];

        if ($task_name) {
            if (!TaskRegistry::has($task_name)) {
                printf("Agent does not exist: %s\n", $task_name);
                return;
            }
        }

        echo 'Reading preferences...';

        $preference_factory = new Preference();
        $preferences = first($preference_factory->find_all());

        // print_r($preferences);

        echo "done\n";

        $stock_factory = new Stock();
        $stocks = $stock_factory->find_all([
            'where_clause' => '`attivo` = 1',
            'limit' => LIMIT
        ]);

        // print_r($stocks);

        $context = new TaskRunnerContext();
        $context->stocks = $stocks;
        $context->preferences = $preferences;
        $context->level = $level;
        $context->task_name = $task_name;

        // print_r($context);

        return TaskRegistry::run_all($context);
    }

    public function flush($level, $task_name = null)
    {
        $tags = ['level' => $level, 'task_name' => $task_name];

        $context = new TaskRunnerContext();
        $context->level = $level;
        $context->task_name = $task_name;

        TaskRegistry::flush_registry($context);
    }

    public static function get_request_params_from_args(array $fields, ?string $args_str = null): array
    {
        $ret = [];

        if ($args_str) {
            $args = explode(' ', $args_str);
            $option_name = null;
            for ($i = 0; $i < count($args); $i += 1) {
                $arg = $args[$i];
                if (strpos($arg, '--') === 0) {
                    // Option name, long form
                    $option_name = substr($arg, 2);
                    if (array_key_exists($option_name, $fields)) {
                        if ($fields[$option_name]['type'] == 'boolean') {
                            $ret[$option_name] = true;
                            unset($option_name);
                        }
                    }
                } elseif (strpos($arg, '-') === 0) {
                    // Option name, short form
                    // TODO:
                } else {
                    // Option value
                    $ret[$option_name] = $arg;
                    unset($option_name);
                }
            }
        }

        return $ret;
    }
}
