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

/**
 *  @class TaskRegistry
 *  @short Registry of runnable tasks.
 */

abstract class TaskRegistry
{
    // Tasks that must always run, at least hourly
    const HIGHEST = -1;
    // Tasks that must run at least once a day
    const HIGH = 0;
    // Tasks that can run at least once a week
    const MEDIUM = 1;
    // Tasks that can safely run once a month
    const LOW = 2;
    // Tasks that can occasionally run
    const LOWEST = 3;
    // Tasks that should only run manually
    const NONE = 1e6;

    private static $registry = [];

    /**
     *  @fn register
     *  @short Registers an task with the registry.
     *  @details Static method to register an task instance with the registry. This
     *  method is typically called at the bottom of the module declaring a class
     *  implementing the Task interface.
     *  @param task The task to register
     *  @see Task
     */
    public static function register(Task $task): bool
    {
        if (array_key_exists($task->name, self::$registry)) {
            return false;
        }
        self::$registry[$task->name] = $task;
        return true;
    }

    /**
     *  @fn get
     *  @short Returns an task from the registry.
     *  @details Static method to obtain an task instance from the registry.
     *  @param task_name The name of the task
     *  @see Task
     */
    public static function get(string $task_name): ?Task
    {
        if (!array_key_exists($task_name, self::$registry)) {
            return null;
        }
        return self::$registry[$task_name];
    }

    /**
     *  @fn has
     *  @short Returns true if the task exists in the registry.
     *  @details Static method to check the existence of an task in the registry.
     *  @param task_name The name of the task
     *  @see Task
     */
    public static function has(string $task_name): bool
    {
        return array_key_exists($task_name, self::$registry);
    }

    /**
     *  @fn run_all
     *  @short Runs all active tasks in the registry.
     *  @details Executes all tasks in the registry, sorting them by the return value
     *  of the <code>get_priority($context)</code> method. Does not run tasks whose
     *  method <code>is_active</code> returns <code>FALSE</code>.
     *  @param context The context of execution for the run registry.
     */
    public static function run_all($context)
    {
        uasort(self::$registry, function ($a, $b) {
            global $context;
            return $a->get_priority($context) - $b->get_priority($context);
        });

        $ret = [];

        foreach (self::$registry as $task_name => $task) {
            if ($task->is_active($context)) {
                if (@$_REQUEST['measure']) {
                    printf("Running task %s with measurement\n", $task->name);
                    $r = $task->run_with_measurement($context);
                } else {
                    printf("Running task %s\n", $task->name);
                    $r = $task->run($context);
                }
                if (is_array($r)) {
                    $ret = $ret + $r;
                } else {
                    $ret = $ret + [$r];
                }
            }
        }

        return $ret;
    }

    public static function flush_registry($context): void
    {
        uasort(self::$registry, function ($a, $b) {
            global $context;
            return $a->get_priority($context) - $b->get_priority($context);
        });

        foreach (self::$registry as $task_name => $task) {
            if ($task->is_active($context)) {
                printf("%s\n", $task->name);
            }
        }
    }

    public static function enumerate($context): array
    {
        $task_names = array_keys(
            array_filter(self::$registry, function ($a) use ($context) {
                return $a->is_active($context);
            })
        );
        sort($task_names);
        return $task_names;
    }

    public static function _clear_registry(): void
    {
        self::$registry = [];
    }
}
