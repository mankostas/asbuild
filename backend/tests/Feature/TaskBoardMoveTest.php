<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Task;
use App\Models\TaskType;
use App\Models\TaskStatus;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskBoardMoveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Tenant::create(['id' => 1, 'name' => 'T', 'features' => ['tasks']]);

        TaskStatus::insert([
            ['slug' => 'draft', 'name' => 'Draft'],
            ['slug' => 'assigned', 'name' => 'Assigned'],
            ['slug' => 'in_progress', 'name' => 'In Progress'],
            ['slug' => 'completed', 'name' => 'Completed'],
        ]);
    }

    protected function user(bool $manage = false): User
    {
        $abilities = ['tasks.update'];
        if ($manage) {
            $abilities[] = 'tasks.manage';
        }
        $role = Role::create([
            'name' => 'User',
            'slug' => $manage ? 'manager' : 'user',
            'tenant_id' => 1,
            'abilities' => $abilities,
            'level' => 1,
        ]);
        $user = User::create([
            'name' => 'U',
            'email' => ($manage ? 'm' : 'u') . '@example.com',
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
            'status_slug' => 'draft',
            'assigned_user_id' => $user->id,
        ]);
    }

    public function test_can_revert_only_one_step_back(): void
    {
        $user = $this->user();
        $task = $this->makeTask($user);

        $this->withHeader('X-Tenant-ID', 1)
            ->patchJson('/api/task-board/move', ['task_id' => $task->id, 'status_slug' => 'assigned', 'index' => 0])
            ->assertStatus(200);

        $this->withHeader('X-Tenant-ID', 1)
            ->patchJson('/api/task-board/move', ['task_id' => $task->id, 'status_slug' => 'draft', 'index' => 0])
            ->assertStatus(200);

        $this->withHeader('X-Tenant-ID', 1)
            ->patchJson('/api/task-board/move', ['task_id' => $task->id, 'status_slug' => 'assigned', 'index' => 0])
            ->assertStatus(200);
        $this->withHeader('X-Tenant-ID', 1)
            ->patchJson('/api/task-board/move', ['task_id' => $task->id, 'status_slug' => 'in_progress', 'index' => 0])
            ->assertStatus(200);

        $this->withHeader('X-Tenant-ID', 1)
            ->patchJson('/api/task-board/move', ['task_id' => $task->id, 'status_slug' => 'draft', 'index' => 0])
            ->assertStatus(422);
    }

    public function test_manager_can_bypass_flow(): void
    {
        $user = $this->user(true);
        $task = $this->makeTask($user);

        $this->withHeader('X-Tenant-ID', 1)
            ->patchJson('/api/task-board/move', ['task_id' => $task->id, 'status_slug' => 'completed', 'index' => 0])
            ->assertStatus(200)
            ->assertJsonPath('data.status_slug', 'completed');
    }

    public function test_reordering_within_same_status_maintains_index(): void
    {
        $user = $this->user();

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

        $taskA = Task::create([
            'tenant_id' => 1,
            'user_id' => $user->id,
            'task_type_id' => $type->id,
            'status_slug' => 'draft',
            'assigned_user_id' => $user->id,
            'board_position' => 1000,
        ]);

        $taskB = Task::create([
            'tenant_id' => 1,
            'user_id' => $user->id,
            'task_type_id' => $type->id,
            'status_slug' => 'draft',
            'assigned_user_id' => $user->id,
            'board_position' => 2000,
        ]);

        $taskC = Task::create([
            'tenant_id' => 1,
            'user_id' => $user->id,
            'task_type_id' => $type->id,
            'status_slug' => 'draft',
            'assigned_user_id' => $user->id,
            'board_position' => 3000,
        ]);

        $this->withHeader('X-Tenant-ID', 1)
            ->patchJson('/api/task-board/move', [
                'task_id' => $taskA->id,
                'status_slug' => 'draft',
                'index' => 2,
            ])
            ->assertStatus(200);

        $order = Task::where('tenant_id', 1)
            ->where('status_slug', 'draft')
            ->orderBy('board_position')
            ->pluck('id')
            ->toArray();

        $this->assertSame([$taskB->id, $taskC->id, $taskA->id], $order);
    }
}

