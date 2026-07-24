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
 *  @interface Task
 *  @short Interface for market tasks.
 *  @details The Task interface models tasks that can be run by a task runner.
 *  Typically, tasks fetch data from remote sources, perform maintenance on the
 *  models, or apply actions like triggering alerts for watchlists.
 */
interface Task
{
    /**
     *  @fn run($context)
     *  @short Runs the task's job.
     *  @details This method is invoked by the #TaskRegistry::run_all method, passing
     *  the execution context to the task.
     *  @param context The execution context
     */
    public function run($context);

    /**
     *  @fn is_active($context)
     *  @short Returns true if the task is active.
     *  @details This method is used by the #TaskRegistry to query the active state
     *  of the task based on the execution context. An task should return <tt>TRUE</tt>
     *  if the #TaskRegistry should invoke its #run method.
     *  @param context The execution context
     */
    public function is_active($context): bool;

    /**
     *  @fn get_priority($context)
     *  @short Returns the priority of the task.
     *  @details This method is used to query the active state of the task based
     *  on the execution context. An task should return a value in the range
     *  between #TaskRegistry::LOWEST to #TaskRegistry::HIGHEST.
     *  @param context The execution context
     */
    public function get_priority($context): int;

    /**
     *  @fn get_fields()
     *  @short Returns fields accepted by this task.
     *  @details Implement this method to pass the task runner UI a list of fields
     *  to control the behavior of this task. The shape of the return value is
     *  illustrated in the base task abstract superclass.
     */
    public function get_fields(): array;
}
