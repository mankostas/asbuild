<?php

namespace Tests\Unit;

use App\Models\Task;
use App\Models\TaskType;
use App\Models\TaskSubtask;
use App\Models\TaskTypeVersion;
use App\Models\Tenant;
use App\Models\User;
use App\Services\StatusFlowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Exceptions\HttpResponseException;
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

    public function test_allows_custom_transitions(): void
    {
        $type = TaskType::create([
            'name' => 'Type',
            'tenant_id' => 1,
            'status_flow_json' => [
                ['draft', 'review'],
                ['review', 'done'],
            ],
        ]);

        $service = new StatusFlowService();

        $this->assertSame(['review'], $service->allowedTransitions('draft', $type));
        $this->assertTrue($service->canTransition('review', 'done', $type));
        $this->assertFalse($service->canTransition('draft', 'assigned', $type));
    }

    public function test_assignee_required(): void
    {
        $type = TaskType::create([
            'name' => 'Type',
            'tenant_id' => 1,
        ]);
        $version = TaskTypeVersion::create([
            'task_type_id' => $type->id,
            'semver' => '1.0.0',
            'statuses' => [
                ['slug' => 'initial'],
                ['slug' => 'final'],
            ],
            'created_by' => 1,
        ]);
        $type->current_version_id = $version->id;
        $type->save();

        $task = Task::create([
            'tenant_id' => 1,
            'user_id' => 1,
            'task_type_id' => $type->id,
            'task_type_version_id' => $version->id,
            'status_slug' => 'initial',
        ]);

        $service = new StatusFlowService();
        try {
            $service->checkConstraints($task, 'final');
            $this->fail('Expected exception not thrown');
        } catch (HttpResponseException $e) {
            $this->assertEquals('assignee_required', $e->getResponse()->getData(true)['code']);
        }
    }

    public function test_incomplete_required_subtasks_block_final(): void
    {
        $type = TaskType::create([
            'name' => 'Type',
            'tenant_id' => 1,
        ]);
        $version = TaskTypeVersion::create([
            'task_type_id' => $type->id,
            'semver' => '1.0.0',
            'statuses' => [
                ['slug' => 'initial'],
                ['slug' => 'final'],
            ],
            'created_by' => 1,
        ]);
        $type->current_version_id = $version->id;
        $type->save();

        $task = Task::create([
            'tenant_id' => 1,
            'user_id' => 1,
            'task_type_id' => $type->id,
            'task_type_version_id' => $version->id,
            'status_slug' => 'initial',
            'assigned_user_id' => 1,
        ]);

        TaskSubtask::create([
            'task_id' => $task->id,
            'title' => 'S',
            'is_required' => true,
            'is_completed' => false,
        ]);

        $service = new StatusFlowService();
        try {
            $service->checkConstraints($task, 'final');
            $this->fail('Expected exception not thrown');
        } catch (HttpResponseException $e) {
            $this->assertEquals('subtasks_incomplete', $e->getResponse()->getData(true)['code']);
        }
    }

    public function test_missing_required_photos_block_final(): void
    {
        $type = TaskType::create([
            'name' => 'Type',
            'tenant_id' => 1,
        ]);
        $version = TaskTypeVersion::create([
            'task_type_id' => $type->id,
            'semver' => '1.0.0',
            'schema_json' => [
                'sections' => [
                    ['photos' => [[ 'key' => 'p1', 'required' => true ]]],
                ],
            ],
            'statuses' => [
                ['slug' => 'initial'],
                ['slug' => 'final'],
            ],
            'created_by' => 1,
        ]);
        $type->current_version_id = $version->id;
        $type->save();

        $task = Task::create([
            'tenant_id' => 1,
            'user_id' => 1,
            'task_type_id' => $type->id,
            'task_type_version_id' => $version->id,
            'status_slug' => 'initial',
            'assigned_user_id' => 1,
        ]);

        $service = new StatusFlowService();
        try {
            $service->checkConstraints($task, 'final');
            $this->fail('Expected exception not thrown');
        } catch (HttpResponseException $e) {
            $this->assertEquals('photos_required', $e->getResponse()->getData(true)['code']);
        }
    }
}
