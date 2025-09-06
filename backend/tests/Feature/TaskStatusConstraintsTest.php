<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Task;
use App\Models\TaskSubtask;
use App\Models\TaskType;
use App\Models\TaskTypeVersion;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskStatusConstraintsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Tenant::create(['id' => 1, 'name' => 'T', 'features' => ['tasks']]);
    }

    protected function authUser(): User
    {
        $role = Role::create([
            'name' => 'User',
            'slug' => 'user',
            'tenant_id' => 1,
            'abilities' => ['tasks.status.update', 'tasks.update'],
            'level' => 1,
        ]);
        $user = User::create([
            'name' => 'U',
            'email' => 'u@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => 1,
            'phone' => '123',
            'address' => 'Street',
        ]);
        $user->roles()->attach($role->id, ['tenant_id' => 1]);
        Sanctum::actingAs($user);
        return $user;
    }

    protected function makeType(array $schema = []): TaskTypeVersion
    {
        $type = TaskType::create([
            'name' => 'Type',
            'tenant_id' => 1,
        ]);
        $version = TaskTypeVersion::create([
            'task_type_id' => $type->id,
            'semver' => '1.0.0',
            'schema_json' => $schema,
            'statuses' => [
                ['slug' => 'initial'],
                ['slug' => 'final'],
            ],
            'status_flow_json' => [
                ['initial', 'final'],
            ],
            'created_by' => 1,
        ]);
        $type->current_version_id = $version->id;
        $type->save();
        return $version;
    }

    public function test_assignee_required_to_leave_initial(): void
    {
        $user = $this->authUser();
        $version = $this->makeType();

        $task = Task::create([
            'tenant_id' => 1,
            'user_id' => $user->id,
            'task_type_id' => $version->task_type_id,
            'task_type_version_id' => $version->id,
            'status' => 'initial',
            'status_slug' => 'initial',
        ]);

        $this->withHeader('X-Tenant-ID', 1)
            ->postJson("/api/tasks/{$task->id}/status", ['status' => 'final'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'assignee_required');
    }

    public function test_required_subtasks_must_be_complete_before_final(): void
    {
        $user = $this->authUser();
        $version = $this->makeType();

        $task = Task::create([
            'tenant_id' => 1,
            'user_id' => $user->id,
            'task_type_id' => $version->task_type_id,
            'task_type_version_id' => $version->id,
            'status' => 'initial',
            'status_slug' => 'initial',
            'assigned_user_id' => $user->id,
        ]);

        TaskSubtask::create([
            'task_id' => $task->id,
            'title' => 'S',
            'is_required' => true,
            'is_completed' => false,
        ]);

        $this->withHeader('X-Tenant-ID', 1)
            ->postJson("/api/tasks/{$task->id}/status", ['status' => 'final'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'subtasks_incomplete');
    }

    public function test_required_photos_must_be_attached_before_final(): void
    {
        $user = $this->authUser();
        $version = $this->makeType([
            'sections' => [
                ['photos' => [
                    ['key' => 'p1', 'required' => true],
                ]],
            ],
        ]);

        $task = Task::create([
            'tenant_id' => 1,
            'user_id' => $user->id,
            'task_type_id' => $version->task_type_id,
            'task_type_version_id' => $version->id,
            'status' => 'initial',
            'status_slug' => 'initial',
            'assigned_user_id' => $user->id,
        ]);

        $this->withHeader('X-Tenant-ID', 1)
            ->postJson("/api/tasks/{$task->id}/status", ['status' => 'final'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'photos_required');
    }
}

