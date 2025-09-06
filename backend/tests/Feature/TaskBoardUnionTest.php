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

class TaskBoardUnionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Tenant::create(['id' => 1, 'name' => 'T', 'features' => ['tasks']]);
        TaskStatus::insert([
            ['slug' => 'draft', 'name' => 'Draft', 'position' => 1],
            ['slug' => 'assigned', 'name' => 'Assigned', 'position' => 2],
            ['slug' => 'done', 'name' => 'Done', 'position' => 3],
        ]);
    }

    protected function authUser(): User
    {
        $role = Role::create([
            'name' => 'User',
            'slug' => 'user',
            'tenant_id' => 1,
            'abilities' => ['tasks.view|tasks.manage', 'tasks.update'],
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

    public function test_columns_union_and_ordering(): void
    {
        $user = $this->authUser();
        $v1 = $this->makeVersion(['draft', 'assigned']);
        $v2 = $this->makeVersion(['assigned', 'done']);
        $this->makeTask($user, $v1, 'assigned');
        $this->makeTask($user, $v2, 'done');

        $response = $this->withHeader('X-Tenant-ID', 1)->getJson('/api/task-board');

        $response->assertStatus(200);
        $this->assertEquals(
            ['draft', 'assigned', 'done'],
            collect($response->json('data'))->pluck('status.slug')->all()
        );
    }

    public function test_filters_change_counts(): void
    {
        $user = $this->authUser();
        $version = $this->makeVersion(['draft']);
        $this->makeTask($user, $version, 'draft', ['assigned_user_id' => $user->id]);
        $this->makeTask($user, $version, 'draft', ['assigned_user_id' => null, 'board_position' => 1]);

        $all = $this->withHeader('X-Tenant-ID', 1)->getJson('/api/task-board');
        $filtered = $this->withHeader('X-Tenant-ID', 1)
            ->getJson('/api/task-board?assignee_id=' . $user->id);

        $all->assertJsonPath('data.0.meta.total', 2);
        $filtered->assertJsonPath('data.0.meta.total', 1);
    }

    public function test_column_pagination_meta(): void
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
}

