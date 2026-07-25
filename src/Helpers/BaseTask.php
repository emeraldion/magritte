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

use Emeraldion\EmeRails\Config;
use Emeraldion\EmeRails\Helpers\Localization;

abstract class BaseTask implements Task
{
    public $name = '__base__';

    const PARAM_ID = 'id';

    public function get_priority($context): int
    {
        return TaskRegistry::MEDIUM;
    }

    public function get_name(): string
    {
        return l(sprintf('task-%s-name', str_replace('_', '-', $this->name)));
    }

    public function get_description(): string
    {
        return l(sprintf('task-%s-description', str_replace('_', '-', $this->name)));
    }

    public function is_active($context): bool
    {
        // printf("Checking whether task '%s' is active...\n", $this->name);
        if (isset($context->level) && $context->level >= $this->get_priority($context)) {
            // printf(
            // 	"Context level (%d) is greater than or equal to this task's priority (%d)\n",
            // 	$context->level,
            // 	$this->get_priority($context)
            // );
            return true;
        }
        if (isset($context->task_name) && $context->task_name == $this->name) {
            // printf(
            // 	"Context task name is set (%s) and matches this task's name (%s)\n",
            // 	$context->task_name,
            // 	$this->name
            // );
            return true;
        }
        // printf("The task '%s' is not active\n", $this->name);
        return false;
    }

    public function run($context)
    {
        $this->log("%s::run\n", get_called_class());

        return true;
    }

    public function get_fields(): array
    {
        // return array(
        //     array(
        //         self::FIELD_LABEL => 'task-common-field-date-label',
        //         self::FIELD_NAME => 'date',
        //         self::FIELD_TYPE => 'string'
        //     ),
        //     array(
        //         self::FIELD_LABEL => 'task-field-forecast-label',
        //         self::FIELD_NAME => 'forecast',
        //         self::FIELD_TYPE => self::FIELD_TYPE_BOOLEAN,
        //         self::FIELD_DEFAULT_CHECKED => false
        //     )
        // );
        return [];
    }

    const FIELD_NAME = 'name';
    const FIELD_TYPE = 'type';
    const FIELD_LABEL = 'label';
    const FIELD_VALUE = 'value';
    const FIELD_DEFAULT_CHECKED = 'default_checked';

    const FIELD_NAME_RETURN = '__return';
    const FIELD_NAME_DRY_RUN = 'dry-run';
    const FIELD_NAME_VERBOSE = 'verbose';
    const FIELD_NAME_MEASURE = 'measure';

    const FIELD_TYPE_BOOLEAN = 'boolean';

    protected function get_common_fields()
    {
        return [
            [
                self::FIELD_NAME => self::FIELD_NAME_RETURN,
                self::FIELD_TYPE => self::FIELD_TYPE_BOOLEAN,
                self::FIELD_VALUE => false
            ],
            [
                self::FIELD_NAME => self::FIELD_NAME_DRY_RUN,
                self::FIELD_LABEL => 'task-common-field-dryrun-label',
                self::FIELD_TYPE => self::FIELD_TYPE_BOOLEAN,
                self::FIELD_VALUE => true
            ],
            [
                self::FIELD_NAME => self::FIELD_NAME_VERBOSE,
                self::FIELD_LABEL => 'task-common-field-verbose-label',
                self::FIELD_TYPE => self::FIELD_TYPE_BOOLEAN,
                self::FIELD_VALUE => true,
                self::FIELD_DEFAULT_CHECKED => true
            ],
            [
                self::FIELD_NAME => self::FIELD_NAME_MEASURE,
                self::FIELD_LABEL => 'task-common-field-measure-label',
                self::FIELD_TYPE => self::FIELD_TYPE_BOOLEAN,
                self::FIELD_VALUE => true,
                self::FIELD_DEFAULT_CHECKED => true
            ]
        ];
    }

    protected function get_option($name, $fallback = null)
    {
        return isset($_REQUEST[$name]) && !empty($_REQUEST[$name]) ? $_REQUEST[$name] : $fallback;
    }

    protected function returns_value(): bool
    {
        return $this->get_option('__return', false);
    }

    protected function validate_date($input)
    {
        if (!$input) {
            return false;
        }
        $valid = true;
        $parts = explode('-', $input);
        if (count($parts) === 3) {
            if (!checkdate($parts[1], $parts[2], $parts[0])) {
                $valid = false;
            }
        } else {
            $valid = false;
        }
        if (!$valid) {
            throw new Exception(sprintf('Not a valid date: %s', $input));
        }
        return $input;
    }

    protected function log($template, ...$args)
    {
        if ($this->get_option('verbose')) {
            printf($template, ...$args);
        }
    }

    public function run_with_measurement($context)
    {
        $elapsed = -hrtime(true);
        $ret = $this->run($context);
        $elapsed += hrtime(true);
        // Convert to milliseconds
        $elapsed /= 1e6;

        printf("%s execution time: %.3f ms\n", get_called_class(), $elapsed);

        return $ret;
    }

    protected function send_metric($metric_name, $value)
    {
        // No-op
    }

    protected function with_db_debug($fn)
    {
        Config::set('DB_DEBUG', true);
        $fn();
        Config::set('DB_DEBUG', false);
    }

    protected function get_identifiers_from_option(): array
    {
        $identifiers = $this->get_option(self::PARAM_ID, '');
        if (Config::get('USE_TRAMEZZINO') && str_starts_with($identifiers, '~')) {
            return TramezzinoHelper::decode($identifiers);
        }
        return explode(',', $identifiers);
    }

    protected function get_items($context, string $classname, callable $filter_fn, int $limit = 100): array
    {
        $items = array_filter(
            $this->get_option(self::PARAM_ID)
                ? array_map(function ($identifier) use ($classname) {
                    if ($item = $classname::find(trim($identifier))) {
                        return $item;
                    }
                    return null;
                }, $this->get_identifiers_from_option())
                : $context->items,
            $filter_fn
        );
        if (count($items) > $limit) {
            $items = array_slice($items, 0, $limit);
        }
        // var_dump($items);
        return $items;
    }

    protected function expire_cached_pages(array $pages): void
    {
        $langs = Localization::$languages;

        foreach ($pages as $params) {
            $controller = $params['controller'];
            $action = isset($params['action']) ? $params['action'] : 'index';
            $id = isset($params['id']) ? "@{$params['id']}" : '';

            foreach ($langs as $lang) {
                $cachefile = sprintf('%s/../caches/%s/%s%s-%s.cached', __DIR__, $controller, $action, $id, $lang);
                if (file_exists($cachefile)) {
                    unlink($cachefile);
                }
            }
        }
    }
}
