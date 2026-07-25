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

namespace Emeraldion\Magritte\Test\Helpers;

use PHPUnit\Framework\TestCase;

use Emeraldion\Magritte\Helpers\BaseTask;
use Emeraldion\Magritte\Helpers\TaskRegistry;

class TaskOne extends BaseTask
{
    public $name = 'one';
}

class TaskTwo extends BaseTask
{
    public $name = 'two';
}

class TaskRegistryTest extends TestCase
{
    public function setUp(): void
    {
        TaskRegistry::_clear_registry();
    }

    public function test_register_once()
    {
        $this->assertTrue(TaskRegistry::register(new TaskOne()));
        $this->assertTrue(TaskRegistry::register(new TaskTwo()));
    }

    public function test_register_twice()
    {
        $this->assertTrue(TaskRegistry::register(new TaskOne()));
        $this->assertFalse(TaskRegistry::register(new TaskOne()));

        $this->assertTrue(TaskRegistry::register(new TaskTwo()));
        $this->assertFalse(TaskRegistry::register(new TaskTwo()));
    }

    public function test_has()
    {
        $this->assertFalse(TaskRegistry::has('one'));
        TaskRegistry::register(new TaskOne());
        $this->assertTrue(TaskRegistry::has('one'));

        $this->assertFalse(TaskRegistry::has('two'));
        TaskRegistry::register(new TaskTwo());
        $this->assertTrue(TaskRegistry::has('two'));
    }

    public function test_get()
    {
        $this->assertNull(TaskRegistry::get('one'));
        $t1 = new TaskOne();
        TaskRegistry::register($t1);
        $this->assertEquals($t1, TaskRegistry::get('one'));

        $this->assertNull(TaskRegistry::get('two'));
        $t2 = new TaskTwo();
        TaskRegistry::register($t2);
        $this->assertEquals($t2, TaskRegistry::get('two'));
    }
}
