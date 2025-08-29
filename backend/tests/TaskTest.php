<?php

use PHPUnit\Framework\TestCase;
use App\Models\Task;
use App\Models\Tenant;

class TaskTest extends TestCase
{
    public function test_task_has_tenant_relationship(): void
    {
        $this->assertTrue(method_exists(Task::class, 'tenant'));
    }

    public function test_tenant_has_tasks_relationship(): void
    {
        $this->assertTrue(method_exists(Tenant::class, 'tasks'));
    }
}
