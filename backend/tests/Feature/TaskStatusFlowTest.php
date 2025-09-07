<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Task;
use App\Models\TaskType;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskStatusFlowTest extends TestCase
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
            'abilities' => ['tasks.status.update', 'tasks.manage', 'tasks.update'],
            'level' => 1,
        ]);
        $user = User::create([
            'name' => 'U',
            'email' => 'u@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => 1,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $user->roles()->attach($role->id, ['tenant_id' => 1]);
        Sanctum::actingAs($user);
        return $user;
    }

    protected function makeTask(User $user): Task
    {
        $type = TaskType::create([
            'name' => 'Type',
            'tenant_id' => 1,
            'statuses' => ['draft' => [], 'assigned' => [], 'in_progress' => [], 'completed' => []],
            'status_flow_json' => [
                ['draft', 'assigned'],
                ['assigned', 'in_progress'],
                ['in_progress', 'completed'],
            ],
        ]);

        return Task::create([
            'tenant_id' => 1,
            'user_id' => $user->id,
            'task_type_id' => $type->id,
            'status' => 'draft',
            'assigned_user_id' => $user->id,
        ]);
    }

    public function test_allows_valid_transition(): void
    {
        $user = $this->authUser();
        $task = $this->makeTask($user);

        $this->withHeader('X-Tenant-ID', 1)
            ->postJson("/api/tasks/{$task->id}/status", ['status' => 'assigned'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'assigned');
    }

    public function test_rejects_invalid_transition(): void
    {
        $user = $this->authUser();
        $task = $this->makeTask($user);

        $this->withHeader('X-Tenant-ID', 1)
            ->postJson("/api/tasks/{$task->id}/status", ['status' => 'completed'])
            ->assertStatus(422)
            ->assertJson(['message' => 'invalid_transition']);
    }

    public function test_defaults_when_no_custom_flow(): void
    {
        $user = $this->authUser();
        $type = TaskType::create([
            'name' => 'Type',
            'tenant_id' => 1,
            'statuses' => ['draft' => [], 'assigned' => [], 'in_progress' => [], 'completed' => []],
            'status_flow_json' => null,
        ]);
        $task = Task::create([
            'tenant_id' => 1,
            'user_id' => $user->id,
            'task_type_id' => $type->id,
            'status' => 'draft',
            'assigned_user_id' => $user->id,
        ]);

        $this->withHeader('X-Tenant-ID', 1)
            ->postJson("/api/tasks/{$task->id}/status", ['status' => 'assigned'])
            ->assertStatus(200);
        $this->withHeader('X-Tenant-ID', 1)
            ->postJson("/api/tasks/{$task->id}/status", ['status' => 'completed'])
            ->assertStatus(422);
    }

    public function test_handles_object_edges_in_flow(): void
    {
        $user = $this->authUser();
        $type = TaskType::create([
            'name' => 'Type',
            'tenant_id' => 1,
            'statuses' => [
                ['slug' => 'draft'],
                ['slug' => 'assigned'],
                ['slug' => 'in_progress'],
                ['slug' => 'completed'],
            ],
            'status_flow_json' => [
                [ ['slug' => 'draft'], ['slug' => 'assigned'] ],
                [ ['slug' => 'assigned'], ['slug' => 'in_progress'] ],
                [ ['slug' => 'in_progress'], ['slug' => 'completed'] ],
            ],
        ]);
        $task = Task::create([
            'tenant_id' => 1,
            'user_id' => $user->id,
            'task_type_id' => $type->id,
            'status' => 'draft',
            'assigned_user_id' => $user->id,
        ]);

        $this->withHeader('X-Tenant-ID', 1)
            ->postJson("/api/tasks/{$task->id}/status", ['status' => 'assigned'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'assigned');
    }
}
