<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\TaskType;
use App\Models\Tenant;
use App\Models\User;
use App\Services\BoardPositionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskBoardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Tenant::create(['id' => 1, 'name' => 'T', 'features' => ['tasks']]);
        TaskStatus::insert([
            ['slug' => 'draft', 'name' => 'Draft', 'position' => 1],
            ['slug' => 'assigned', 'name' => 'Assigned', 'position' => 2],
        ]);
    }

    protected function authUser(): User
    {
        $role = Role::create([
            'name' => 'User',
            'slug' => 'user',
            'tenant_id' => 1,
            'abilities' => ['tasks.view', 'tasks.update'],
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

    protected function makeTask(User $user): Task
    {
        $type = TaskType::create([
            'name' => 'Type',
            'tenant_id' => 1,
            'statuses' => ['draft' => [], 'assigned' => []],
            'status_flow_json' => [
                ['draft', 'assigned'],
            ],
        ]);

        return Task::create([
            'tenant_id' => 1,
            'user_id' => $user->id,
            'task_type_id' => $type->id,
            'status_slug' => 'draft',
            'board_position' => 1000,
        ]);
    }

    public function test_move_valid(): void
    {
        $user = $this->authUser();
        $task = $this->makeTask($user);

        $this->withHeader('X-Tenant-ID', 1)
            ->patchJson('/api/task-board/move', [
                'task_id' => $task->id,
                'status_slug' => 'assigned',
                'index' => 0,
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.status_slug', 'assigned');
    }

    public function test_move_invalid_transition(): void
    {
        $user = $this->authUser();
        $task = $this->makeTask($user);

        $this->withHeader('X-Tenant-ID', 1)
            ->patchJson('/api/task-board/move', [
                'task_id' => $task->id,
                'status_slug' => 'draft',
                'index' => 0,
            ])
            ->assertStatus(422);
    }

    public function test_index_returns_normalized_columns(): void
    {
        $user = $this->authUser();
        $task = $this->makeTask($user);

        $this->withHeader('X-Tenant-ID', 1)
            ->getJson('/api/task-board')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    [
                        'status' => ['slug', 'name'],
                        'tasks' => [
                            ['id', 'status_slug', 'board_position'],
                        ],
                        'meta' => ['total', 'has_more'],
                    ],
                ],
            ])
            ->assertJsonPath('data.0.status.slug', 'draft')
            ->assertJsonPath('data.0.tasks.0.id', $task->id)
            ->assertJsonPath('data.0.meta.total', 1)
            ->assertJsonPath('data.0.meta.has_more', false)
            ->assertJsonMissingPath('data.0.tasks.data');
    }

    public function test_index_reports_total_and_overflow_flag(): void
    {
        $user = $this->authUser();
        $type = TaskType::create([
            'name' => 'Type',
            'tenant_id' => 1,
            'statuses' => ['draft' => [], 'assigned' => []],
            'status_flow_json' => [
                ['draft', 'assigned'],
            ],
        ]);

        for ($i = 0; $i < 55; $i++) {
            Task::create([
                'tenant_id' => 1,
                'user_id' => $user->id,
                'task_type_id' => $type->id,
                'status_slug' => 'draft',
                'board_position' => $i,
            ]);
        }

        $response = $this->withHeader('X-Tenant-ID', 1)->getJson('/api/task-board');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.meta.total', 55)
            ->assertJsonPath('data.0.meta.has_more', true);

        $this->assertCount(50, $response->json('data.0.tasks'));
    }

    public function test_move_updates_board_position(): void
    {
        $user = $this->authUser();
        $task = $this->makeTask($user);
        Task::create([
            'tenant_id' => 1,
            'user_id' => $user->id,
            'task_type_id' => $task->task_type_id,
            'status_slug' => 'assigned',
            'board_position' => 1000,
        ]);

        $this->withHeader('X-Tenant-ID', 1)
            ->patchJson('/api/task-board/move', [
                'task_id' => $task->id,
                'status_slug' => 'assigned',
                'index' => 0,
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'board_position' => 0,
            'status_slug' => 'assigned',
        ]);
    }
}
