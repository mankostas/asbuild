<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\TaskType;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskBoardTenantVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_sees_tasks_for_selected_tenant(): void
    {
        $tenant1 = Tenant::create(['id' => 1, 'name' => 'T1', 'features' => ['tasks']]);
        $tenant2 = Tenant::create(['id' => 2, 'name' => 'T2', 'features' => ['tasks']]);

        TaskStatus::create(['slug' => 'todo', 'name' => 'To Do', 'position' => 1]);

        $type1 = TaskType::create(['tenant_id' => $tenant1->id, 'name' => 'Type1', 'statuses' => ['todo' => []]]);
        $type2 = TaskType::create(['tenant_id' => $tenant2->id, 'name' => 'Type2', 'statuses' => ['todo' => []]]);

        $u1 = User::create([
            'name' => 'U1',
            'email' => 'u1@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant1->id,
            'phone' => '1',
            'address' => 'A',
        ]);
        $u2 = User::create([
            'name' => 'U2',
            'email' => 'u2@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant2->id,
            'phone' => '1',
            'address' => 'A',
        ]);

        Task::create(['tenant_id' => $tenant1->id, 'user_id' => $u1->id, 'task_type_id' => $type1->id, 'status' => 'todo', 'status_slug' => 'todo', 'title' => 'T1 Task']);
        Task::create(['tenant_id' => $tenant2->id, 'user_id' => $u2->id, 'task_type_id' => $type2->id, 'status' => 'todo', 'status_slug' => 'todo', 'title' => 'T2 Task']);

        $root = Tenant::create(['id' => 999, 'name' => 'Root']);
        $role = Role::create([
            'name' => 'SuperAdmin',
            'slug' => 'super_admin',
            'tenant_id' => $root->id,
            'abilities' => ['*'],
            'level' => 0,
        ]);

        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $root->id,
            'phone' => '1',
            'address' => 'A',
        ]);
        $user->roles()->attach($role->id, ['tenant_id' => $root->id]);
        Sanctum::actingAs($user);

        $response = $this->withHeader('X-Tenant-ID', $tenant1->id)->getJson('/api/task-board');
        $response->assertStatus(200);
        $this->assertEquals('T1 Task', $response->json('data.0.tasks.0.title'));
        $this->assertCount(1, $response->json('data.0.tasks'));
    }
}
