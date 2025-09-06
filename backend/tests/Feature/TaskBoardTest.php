<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\TaskType;
use App\Models\TaskTypeVersion;
use App\Models\Tenant;
use App\Models\User;
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

    protected function makeVersion(array $statuses): TaskTypeVersion
    {
        $type = TaskType::create([
            'name' => 'Type' . uniqid(),
            'tenant_id' => 1,
        ]);
        $version = TaskTypeVersion::create([
            'task_type_id' => $type->id,
            'semver' => '1.0.0',
            'statuses' => collect($statuses)->map(fn ($s) => ['slug' => $s])->all(),
            'status_flow_json' => [[$statuses[0], $statuses[1] ?? $statuses[0]]],
            'created_by' => 1,
            'published_at' => now(),
        ]);
        $type->current_version_id = $version->id;
        $type->save();
        return $version;
    }

    protected function makeTask(User $user, TaskTypeVersion $version, string $status = 'draft', array $extra = []): Task
    {
        return Task::create(array_merge([
            'tenant_id' => 1,
            'user_id' => $user->id,
            'task_type_id' => $version->task_type_id,
            'task_type_version_id' => $version->id,
            'status_slug' => $status,
            'board_position' => $extra['board_position'] ?? 1000,
        ], $extra));
    }

    public function test_move_valid(): void
    {
        $user = $this->authUser();
        $version = $this->makeVersion(['draft', 'assigned']);
        $task = $this->makeTask($user, $version, 'draft', ['assigned_user_id' => $user->id]);

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
        $version = $this->makeVersion(['draft', 'assigned']);
        $task = $this->makeTask($user, $version, 'draft', ['assigned_user_id' => $user->id]);

        $this->withHeader('X-Tenant-ID', 1)
            ->patchJson('/api/task-board/move', [
                'task_id' => $task->id,
                'status_slug' => 'draft',
                'index' => 0,
            ])
            ->assertStatus(422);
    }

    public function test_index_returns_empty_when_no_columns(): void
    {
        $this->authUser();

        $this->withHeader('X-Tenant-ID', 1)
            ->getJson('/api/task-board')
            ->assertStatus(200)
            ->assertJson(['data' => []]);
    }

    public function test_index_returns_normalized_columns(): void
    {
        $user = $this->authUser();
        $version = $this->makeVersion(['draft', 'assigned']);
        $task = $this->makeTask($user, $version);

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
        $version = $this->makeVersion(['draft']);
        for ($i = 0; $i < 55; $i++) {
            $this->makeTask($user, $version, 'draft', ['board_position' => $i]);
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
        $version = $this->makeVersion(['draft', 'assigned']);
        $task = $this->makeTask($user, $version, 'draft', ['assigned_user_id' => $user->id]);
        $this->makeTask($user, $version, 'assigned', ['board_position' => 1000]);

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

    public function test_columns_union_of_statuses_across_types(): void
    {
        TaskStatus::create(['slug' => 'done', 'name' => 'Done', 'position' => 3]);
        $user = $this->authUser();
        $v1 = $this->makeVersion(['draft', 'assigned']);
        $v2 = $this->makeVersion(['draft', 'done']);
        $this->makeTask($user, $v1, 'assigned');
        $this->makeTask($user, $v2, 'done');

        $response = $this->withHeader('X-Tenant-ID', 1)->getJson('/api/task-board');

        $response->assertStatus(200);
        $this->assertEquals(
            ['draft', 'assigned', 'done'],
            collect($response->json('data'))->pluck('status.slug')->all()
        );
    }

    public function test_column_endpoint_paginates(): void
    {
        $user = $this->authUser();
        $version = $this->makeVersion(['draft']);
        for ($i = 0; $i < 55; $i++) {
            $this->makeTask($user, $version, 'draft', ['board_position' => $i]);
        }

        $page2 = $this->withHeader('X-Tenant-ID', 1)
            ->getJson('/api/task-board/column?status=draft&page=2');

        $page2->assertStatus(200)
            ->assertJsonPath('meta.total', 55)
            ->assertJsonPath('meta.has_more', false);
        $this->assertCount(5, $page2->json('data'));
    }

    public function test_index_filters_by_assignee(): void
    {
        $user = $this->authUser();
        $version = $this->makeVersion(['draft']);
        $task1 = $this->makeTask($user, $version, 'draft', ['assigned_user_id' => $user->id]);
        $this->makeTask($user, $version, 'draft', ['assigned_user_id' => null, 'board_position' => 1]);

        $response = $this->withHeader('X-Tenant-ID', 1)
            ->getJson('/api/task-board?assignee_id=' . $user->id);

        $response->assertStatus(200)
            ->assertJsonPath('data.0.meta.total', 1);
        $this->assertEquals($task1->id, $response->json('data.0.tasks.0.id'));
    }

    public function test_index_filters_mine(): void
    {
        $user = $this->authUser();
        $version = $this->makeVersion(['draft']);
        $mine = $this->makeTask($user, $version, 'draft', ['assigned_user_id' => $user->id]);
        $this->makeTask($user, $version, 'draft', ['assigned_user_id' => null, 'board_position' => 1]);

        $response = $this->withHeader('X-Tenant-ID', 1)
            ->getJson('/api/task-board?mine=1');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.meta.total', 1);
        $this->assertEquals($mine->id, $response->json('data.0.tasks.0.id'));
    }

    public function test_index_filters_due_today(): void
    {
        $user = $this->authUser();
        $version = $this->makeVersion(['draft']);
        $today = $this->makeTask($user, $version, 'draft', ['due_at' => now()]);
        $this->makeTask($user, $version, 'draft', ['due_at' => now()->addDay(), 'board_position' => 1]);

        $response = $this->withHeader('X-Tenant-ID', 1)
            ->getJson('/api/task-board?due_today=1');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.meta.total', 1);
        $this->assertEquals($today->id, $response->json('data.0.tasks.0.id'));
    }

    public function test_index_filters_breached_only(): void
    {
        $user = $this->authUser();
        $version = $this->makeVersion(['draft']);
        $breached = $this->makeTask($user, $version, 'draft', ['sla_end_at' => now()->subHour()]);
        $this->makeTask($user, $version, 'draft', ['sla_end_at' => now()->addHour(), 'board_position' => 1]);

        $response = $this->withHeader('X-Tenant-ID', 1)
            ->getJson('/api/task-board?breached_only=1');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.meta.total', 1);
        $this->assertEquals($breached->id, $response->json('data.0.tasks.0.id'));
    }
}

