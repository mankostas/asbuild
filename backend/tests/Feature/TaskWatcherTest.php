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
use App\Support\PublicIdGenerator;

class TaskWatcherTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_follow_and_unfollow_task(): void
    {
        $tenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'T', 'features' => ['tasks']
        ]);
        $reporter = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Reporter',
            'email' => 'r@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $task = Task::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => $tenant->id, 'user_id' => $reporter->id
        ]);

        $role = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Watcher',
            'slug' => 'watcher',
            'tenant_id' => $tenant->id,
            'abilities' => ['tasks.watch', 'tasks.manage'],
            'level' => 1,
        ]);

        $user = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'User',
            'email' => 'u@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $user->roles()->attach($role->id, ['tenant_id' => $tenant->id]);
        Sanctum::actingAs($user);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson("/api/tasks/{$task->id}/watch")
            ->assertStatus(201);

        $this->assertDatabaseHas('task_watchers', [
            'task_id' => $task->id,
            'user_id' => $user->id,
        ]);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->deleteJson("/api/tasks/{$task->id}/watch")
            ->assertStatus(200);

        $this->assertDatabaseMissing('task_watchers', [
            'task_id' => $task->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_cross_tenant_watch_fails(): void
    {
        $tenant1 = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'One', 'features' => ['tasks']
        ]);
        $tenant2 = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Two', 'features' => ['tasks']
        ]);

        $reporter = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Reporter',
            'email' => 'r@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant1->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $task = Task::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => $tenant1->id, 'user_id' => $reporter->id
        ]);

        $role = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Watcher',
            'slug' => 'watcher',
            'tenant_id' => $tenant2->id,
            'abilities' => ['tasks.watch', 'tasks.manage'],
            'level' => 1,
        ]);
        $user = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'User',
            'email' => 'u@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant2->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $user->roles()->attach($role->id, ['tenant_id' => $tenant2->id]);
        Sanctum::actingAs($user);

        $this->withHeader('X-Tenant-ID', $tenant2->id)
            ->postJson("/api/tasks/{$task->id}/watch")
            ->assertStatus(403);

        $this->assertDatabaseMissing('task_watchers', [
            'task_id' => $task->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_default_watchers_added(): void
    {
        $tenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'T', 'features' => ['tasks']
        ]);
        $reporter = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Reporter',
            'email' => 'r@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $assignee = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Assignee',
            'email' => 'a@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $mentioned = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Mention',
            'email' => 'm@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $mentionRole = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Viewer',
            'slug' => 'viewer',
            'tenant_id' => $tenant->id,
            'abilities' => ['tasks.view', 'tasks.manage'],
            'level' => 1,
        ]);
        $mentioned->roles()->attach($mentionRole->id, ['tenant_id' => $tenant->id]);

        $type = TaskType::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Type',
            'schema_json' => [
                'sections' => [
                    [
                        'key' => 'main',
                        'label' => 'Main',
                        'fields' => [
                            ['key' => 'assignee', 'label' => 'Assignee', 'type' => 'assignee'],
                        ],
                    ],
                ],
            ],
            'tenant_id' => $tenant->id,
        ]);

        $role = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Creator',
            'slug' => 'creator',
            'tenant_id' => $tenant->id,
            'abilities' => ['tasks.create', 'tasks.update', 'tasks.comment.create', 'tasks.manage', 'tasks.watch'],
            'level' => 1,
        ]);
        $reporter->roles()->attach($role->id, ['tenant_id' => $tenant->id]);
        Sanctum::actingAs($reporter);

        $payload = [
            'task_type_id' => $type->id,
            'assignee' => ['id' => $assignee->id],
        ];
        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/tasks', $payload)
            ->assertStatus(201);
        $taskId = Task::first()->id;

        $this->assertDatabaseHas('task_watchers', ['task_id' => $taskId, 'user_id' => $reporter->id]);
        $this->assertDatabaseHas('task_watchers', ['task_id' => $taskId, 'user_id' => $assignee->id]);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson("/api/tasks/{$taskId}/comments", [
                'body' => 'hello',
                'mentions' => [$mentioned->id],
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('task_watchers', ['task_id' => $taskId, 'user_id' => $mentioned->id]);
    }
}
