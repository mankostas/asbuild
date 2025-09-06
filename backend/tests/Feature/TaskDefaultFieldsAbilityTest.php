<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Task;
use App\Models\TaskType;
use App\Models\TaskTypeVersion;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskDefaultFieldsAbilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Tenant::create(['id' => 1, 'name' => 'T', 'features' => ['tasks']]);
    }

    protected function makeVersion(): TaskTypeVersion
    {
        $type = TaskType::create([
            'name' => 'Type',
            'tenant_id' => 1,
        ]);
        $version = TaskTypeVersion::create([
            'task_type_id' => $type->id,
            'semver' => '1.0.0',
            'statuses' => [
                ['slug' => 'open'],
                ['slug' => 'done'],
            ],
            'status_flow_json' => [['open', 'done']],
            'created_by' => 1,
        ]);
        $type->current_version_id = $version->id;
        $type->save();

        return $version;
    }

    protected function makeUser(array $abilities): User
    {
        $role = Role::create([
            'name' => 'R',
            'slug' => 'r',
            'tenant_id' => 1,
            'abilities' => $abilities,
            'level' => 1,
        ]);
        $user = User::create([
            'name' => 'U',
            'email' => uniqid() . '@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => 1,
            'phone' => '123',
            'address' => 'Street',
        ]);
        $user->roles()->attach($role->id, ['tenant_id' => 1]);
        Sanctum::actingAs($user);
        return $user;
    }

    public function test_status_update_requires_ability(): void
    {
        $version = $this->makeVersion();
        $user = $this->makeUser(['tasks.update']);
        $task = Task::create([
            'tenant_id' => 1,
            'user_id' => $user->id,
            'task_type_id' => $version->task_type_id,
            'task_type_version_id' => $version->id,
            'status' => 'open',
            'status_slug' => 'open',
        ]);

        $this->withHeader('X-Tenant-ID', 1)
            ->postJson("/api/tasks/{$task->id}/status", ['status' => 'done'])
            ->assertStatus(403);

        $user2 = $this->makeUser(['tasks.update', 'tasks.status.update']);
        $task2 = Task::create([
            'tenant_id' => 1,
            'user_id' => $user2->id,
            'task_type_id' => $version->task_type_id,
            'task_type_version_id' => $version->id,
            'status' => 'open',
            'status_slug' => 'open',
        ]);

        $this->withHeader('X-Tenant-ID', 1)
            ->postJson("/api/tasks/{$task2->id}/status", ['status' => 'done'])
            ->assertOk()
            ->assertJsonPath('data.status', 'done');
    }

    public function test_sla_override_requires_ability_on_update(): void
    {
        $version = $this->makeVersion();
        $user = $this->makeUser(['tasks.update']);
        $task = Task::create([
            'tenant_id' => 1,
            'user_id' => $user->id,
            'task_type_id' => $version->task_type_id,
            'task_type_version_id' => $version->id,
            'status' => 'open',
            'status_slug' => 'open',
        ]);

        $this->withHeader('X-Tenant-ID', 1)
            ->patchJson("/api/tasks/{$task->id}", [
                'sla_start_at' => '2025-01-01T00:00:00Z',
                'sla_end_at' => '2025-01-02T00:00:00Z',
            ])->assertOk();

        $task->refresh();
        $this->assertNull($task->sla_start_at);
        $this->assertNull($task->sla_end_at);

        $user2 = $this->makeUser(['tasks.update', 'tasks.sla.override']);
        $task2 = Task::create([
            'tenant_id' => 1,
            'user_id' => $user2->id,
            'task_type_id' => $version->task_type_id,
            'task_type_version_id' => $version->id,
            'status' => 'open',
            'status_slug' => 'open',
        ]);

        $this->withHeader('X-Tenant-ID', 1)
            ->patchJson("/api/tasks/{$task2->id}", [
                'sla_start_at' => '2025-01-01T00:00:00Z',
                'sla_end_at' => '2025-01-02T00:00:00Z',
            ])->assertOk();

        $task2->refresh();
        $this->assertEquals('2025-01-01T00:00:00.000000Z', $task2->sla_start_at?->toISOString());
        $this->assertEquals('2025-01-02T00:00:00.000000Z', $task2->sla_end_at?->toISOString());
    }
}

