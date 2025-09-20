<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use App\Models\TaskComment;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Support\PublicIdGenerator;

class TaskCommentMentionTest extends TestCase
{
    use RefreshDatabase;

    public function test_comment_mentions_persist_on_model(): void
    {
        $tenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'T', 'features' => ['tasks']
        ]);
        $author = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'A',
            'email' => 'a@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
        ]);
        $mentioned = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'M',
            'email' => 'm@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
        ]);
        $task = Task::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => $tenant->id,
            'user_id' => $author->id,
            'title' => 'T1',
        ]);

        $comment = TaskComment::create([
            'public_id' => PublicIdGenerator::generate(),
            'task_id' => $task->id,
            'user_id' => $author->id,
            'body' => 'Hi',
        ]);
        $comment->mentions()->attach($mentioned->id);

        $this->assertDatabaseHas('task_comment_mentions', [
            'user_id' => $mentioned->id,
            'task_comment_id' => $comment->id,
        ]);
    }

    public function test_cross_tenant_mention_fails(): void
    {
        $tenant1 = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'T1', 'features' => ['tasks']
        ]);
        $tenant2 = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'T2', 'features' => ['tasks']
        ]);
        $role1 = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'User',
            'slug' => 'user',
            'tenant_id' => $tenant1->id,
            'abilities' => ['*'],
            'level' => 1,
        ]);
        $role2 = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'User',
            'slug' => 'user',
            'tenant_id' => $tenant2->id,
            'abilities' => ['tasks.view'],
            'level' => 1,
        ]);

        $author = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'A',
            'email' => 'a2@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant1->id,
        ]);
        $author->roles()->attach($role1->id, ['tenant_id' => $tenant1->id]);

        $other = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'O',
            'email' => 'o@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant2->id,
        ]);
        $other->roles()->attach($role2->id, ['tenant_id' => $tenant2->id]);

        $task = Task::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => $tenant1->id,
            'user_id' => $author->id,
            'title' => 'T1',
        ]);

        Sanctum::actingAs($author);
        app()->instance('tenant_id', $tenant1->id);

        $this->withHeader('X-Tenant-ID', $tenant1->public_id)->postJson("/api/tasks/{$task->public_id}/comments", [
            'body' => 'Hi',
            'mentions' => [$other->public_id],
        ])->assertStatus(422);
        app()->forgetInstance('tenant_id');
    }
}
