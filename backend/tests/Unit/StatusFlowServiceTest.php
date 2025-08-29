<?php

namespace Tests\Unit;

use App\Models\Task;
use App\Models\TaskType;
use App\Models\TaskSubtask;
use App\Models\Tenant;
use App\Models\User;
use App\Services\StatusFlowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatusFlowServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Tenant::create(['id' => 1, 'name' => 'T', 'features' => ['tasks']]);
        User::create([
            'name' => 'U',
            'email' => 'u@example.com',
            'password' => 'secret',
            'tenant_id' => 1,
            'phone' => '1',
            'address' => 'A',
        ]);
    }

    public function test_detects_missing_required_field(): void
    {
        $type = TaskType::create([
            'name' => 'Type',
            'tenant_id' => 1,
            'schema_json' => [
                'sections' => [
                    ['fields' => [
                        ['key' => 'title', 'type' => 'text', 'required' => true],
                    ]],
                ],
            ],
        ]);

        $task = Task::create([
            'tenant_id' => 1,
            'user_id' => 1,
            'task_type_id' => $type->id,
            'status' => 'in_progress',
            'form_data' => [],
        ]);

        $service = new StatusFlowService();
        $this->assertSame('missing_field', $service->checkConstraints($task, 'completed'));
    }

    public function test_detects_missing_photo(): void
    {
        $type = TaskType::create([
            'name' => 'Type',
            'tenant_id' => 1,
            'schema_json' => [
                'sections' => [
                    ['fields' => [
                        ['key' => 'after_photo', 'type' => 'photo', 'required' => true],
                    ]],
                ],
            ],
        ]);

        $task = Task::create([
            'tenant_id' => 1,
            'user_id' => 1,
            'task_type_id' => $type->id,
            'status' => 'in_progress',
            'form_data' => ['after_photo' => []],
        ]);

        $service = new StatusFlowService();
        $this->assertSame('missing_photo', $service->checkConstraints($task, 'completed'));
    }

    public function test_detects_incomplete_subtasks(): void
    {
        $type = TaskType::create([
            'name' => 'Type',
            'tenant_id' => 1,
            'schema_json' => [],
        ]);

        $task = Task::create([
            'tenant_id' => 1,
            'user_id' => 1,
            'task_type_id' => $type->id,
            'status' => 'in_progress',
        ]);

        TaskSubtask::create(['task_id' => $task->id, 'title' => 'S', 'is_completed' => false]);

        $service = new StatusFlowService();
        $this->assertSame('subtasks_incomplete', $service->checkConstraints($task, 'completed'));
    }
}
