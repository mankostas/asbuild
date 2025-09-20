<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use App\Policies\TaskPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use App\Support\PublicIdGenerator;

class TaskAbilityRestrictionTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_without_create_delete_cannot_create_or_delete_tasks(): void
    {
        $tenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'T', 'features' => ['tasks']
        ]);

        $role = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Contributor',
            'slug' => 'contributor',
            'tenant_id' => $tenant->id,
            'abilities' => ['tasks.status.update', 'tasks.comment.create', 'tasks.attach.upload'],
            'level' => 1,
        ]);

        $user = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'U',
            'email' => 'u@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $user->roles()->attach($role->id, ['tenant_id' => $tenant->id]);
        Sanctum::actingAs($user);

        $this->assertFalse(app(TaskPolicy::class)->create($user));

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/tasks', [])
            ->assertStatus(403);

        $task = Task::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => $tenant->id, 'user_id' => $user->id
        ]);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->deleteJson("/api/tasks/{$task->id}")
            ->assertStatus(403);
    }
}

