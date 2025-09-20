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
use App\Support\PublicIdGenerator;

class TaskBoardMoveTest extends TestCase
{
    use RefreshDatabase;

    protected string $tenantPublicId;

    protected function setUp(): void
    {
        parent::setUp();
        $tenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'id' => 1, 'name' => 'T', 'features' => ['tasks']
        ]);

        $this->tenantPublicId = $tenant->public_id;

        TaskStatus::create([
            'public_id' => PublicIdGenerator::generate(),
            'slug' => 'draft', 'name' => 'Draft', 'tenant_id' => 1
        ]);
        TaskStatus::create([
            'public_id' => PublicIdGenerator::generate(),
            'slug' => 'assigned', 'name' => 'Assigned', 'tenant_id' => 1
        ]);
        TaskStatus::create([
            'public_id' => PublicIdGenerator::generate(),
            'slug' => 'in_progress', 'name' => 'In Progress', 'tenant_id' => 1
        ]);
        TaskStatus::create([
            'public_id' => PublicIdGenerator::generate(),
            'slug' => 'completed', 'name' => 'Completed', 'tenant_id' => 1
        ]);
    }

    protected function user(bool $manage = false): User
    {
        $abilities = ['tasks.update'];
        if ($manage) {
            $abilities[] = 'tasks.manage';
        }
        $role = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'User',
            'slug' => $manage ? 'manager' : 'user',
            'tenant_id' => 1,
            'abilities' => $abilities,
            'level' => 1,
        ]);
        $user = User::create([
            'public_id' => PublicIdGenerator::generate(),
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
            'public_id' => PublicIdGenerator::generate(),
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
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => 1,
            'user_id' => $user->id,
            'task_type_id' => $type->id,
            'status_slug' => \App\Models\TaskStatus::prefixSlug('draft', 1),
            'assigned_user_id' => $user->id,
        ]);
    }

    public function test_can_revert_only_one_step_back(): void
    {
        $user = $this->user();
        $task = $this->makeTask($user);

        $this->withHeader('X-Tenant-ID', $this->tenantPublicId)
            ->patchJson('/api/task-board/move', ['task_id' => $task->public_id, 'status_slug' => 'assigned', 'index' => 0])
            ->assertStatus(200);

        $this->withHeader('X-Tenant-ID', $this->tenantPublicId)
            ->patchJson('/api/task-board/move', ['task_id' => $task->public_id, 'status_slug' => 'draft', 'index' => 0])
            ->assertStatus(200);

        $this->withHeader('X-Tenant-ID', $this->tenantPublicId)
            ->patchJson('/api/task-board/move', ['task_id' => $task->public_id, 'status_slug' => 'assigned', 'index' => 0])
            ->assertStatus(200);
        $this->withHeader('X-Tenant-ID', $this->tenantPublicId)
            ->patchJson('/api/task-board/move', ['task_id' => $task->public_id, 'status_slug' => 'in_progress', 'index' => 0])
            ->assertStatus(200);

        $this->withHeader('X-Tenant-ID', $this->tenantPublicId)
            ->patchJson('/api/task-board/move', ['task_id' => $task->public_id, 'status_slug' => 'draft', 'index' => 0])
            ->assertStatus(422);
    }

    public function test_manager_can_bypass_flow(): void
    {
        $user = $this->user(true);
        $task = $this->makeTask($user);

        $this->withHeader('X-Tenant-ID', $this->tenantPublicId)
            ->patchJson('/api/task-board/move', ['task_id' => $task->public_id, 'status_slug' => 'completed', 'index' => 0])
            ->assertStatus(200)
            ->assertJsonPath('data.status_slug', 'completed');
    }

    public function test_reordering_within_same_status_maintains_index(): void
    {
        $user = $this->user();

        $type = TaskType::create([
            'public_id' => PublicIdGenerator::generate(),
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
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => 1,
            'user_id' => $user->id,
            'task_type_id' => $type->id,
            'status_slug' => \App\Models\TaskStatus::prefixSlug('draft', 1),
            'assigned_user_id' => $user->id,
            'board_position' => 1000,
        ]);

        $taskB = Task::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => 1,
            'user_id' => $user->id,
            'task_type_id' => $type->id,
            'status_slug' => \App\Models\TaskStatus::prefixSlug('draft', 1),
            'assigned_user_id' => $user->id,
            'board_position' => 2000,
        ]);

        $taskC = Task::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => 1,
            'user_id' => $user->id,
            'task_type_id' => $type->id,
            'status_slug' => \App\Models\TaskStatus::prefixSlug('draft', 1),
            'assigned_user_id' => $user->id,
            'board_position' => 3000,
        ]);

        $this->withHeader('X-Tenant-ID', $this->tenantPublicId)
            ->patchJson('/api/task-board/move', [
                'task_id' => $taskA->public_id,
                'status_slug' => 'draft',
                'index' => 2,
            ])
            ->assertStatus(200);

        $order = Task::where('tenant_id', 1)
            ->where('status_slug', \App\Models\TaskStatus::prefixSlug('draft', 1))
            ->orderBy('board_position')
            ->pluck('public_id')
            ->toArray();

        $this->assertSame([$taskB->public_id, $taskC->public_id, $taskA->public_id], $order);
    }
}

