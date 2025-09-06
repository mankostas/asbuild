<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\TaskStatus;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskStatusTenantVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_global_and_tenant_statuses_for_tenant_scope(): void
    {
        Tenant::create(['id' => 1, 'name' => 'T1', 'features' => ['tasks']]);
        Tenant::create(['id' => 2, 'name' => 'T2', 'features' => ['tasks']]);

        TaskStatus::create(['slug' => 'global', 'name' => 'Global', 'tenant_id' => null]);
        TaskStatus::create(['slug' => 't1', 'name' => 'Tenant One', 'tenant_id' => 1]);
        TaskStatus::create(['slug' => 't2', 'name' => 'Tenant Two', 'tenant_id' => 2]);

        $role = Role::create([
            'name' => 'Admin',
            'slug' => 'admin',
            'tenant_id' => 1,
            'abilities' => ['task_statuses.manage'],
            'level' => 1,
        ]);

        $user = User::create([
            'name' => 'User',
            'email' => 'user@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => 1,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $user->roles()->attach($role->id, ['tenant_id' => 1]);
        Sanctum::actingAs($user);

        $response = $this->withHeader('X-Tenant-ID', 1)->getJson('/api/task-statuses');

        $response->assertStatus(200);
        $names = collect($response->json('data'))->pluck('name')->sort()->values()->all();
        $this->assertEquals(['Global', 'Tenant One'], $names);
    }

    public function test_super_admin_sees_global_and_tenant_statuses_with_header(): void
    {
        Tenant::create(['id' => 1, 'name' => 'T1', 'features' => ['tasks']]);
        Tenant::create(['id' => 2, 'name' => 'T2', 'features' => ['tasks']]);

        TaskStatus::create(['slug' => 'global', 'name' => 'Global', 'tenant_id' => null]);
        TaskStatus::create(['slug' => 't1', 'name' => 'Tenant One', 'tenant_id' => 1]);
        TaskStatus::create(['slug' => 't2', 'name' => 'Tenant Two', 'tenant_id' => 2]);

        $root = Tenant::create(['id' => 999, 'name' => 'Root']);
        $role = Role::create([
            'name' => 'SuperAdmin',
            'slug' => 'super_admin',
            'tenant_id' => $root->id,
            'abilities' => ['*'],
            'level' => 0,
        ]);

        $user = User::create([
            'name' => 'Super',
            'email' => 'super@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $root->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $user->roles()->attach($role->id, ['tenant_id' => $root->id]);
        Sanctum::actingAs($user);

        $response = $this->withHeader('X-Tenant-ID', 1)
            ->getJson('/api/task-statuses?scope=tenant');

        $response->assertStatus(200);
        $names = collect($response->json('data'))->pluck('name')->sort()->values()->all();
        $this->assertEquals(['Global', 'Tenant One'], $names);
    }
}
