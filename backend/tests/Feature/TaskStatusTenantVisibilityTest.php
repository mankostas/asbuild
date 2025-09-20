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
use App\Support\PublicIdGenerator;

class TaskStatusTenantVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_global_and_tenant_statuses_for_tenant_scope(): void
    {
        Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'id' => 1, 'name' => 'T1', 'features' => ['tasks']
        ]);
        Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'id' => 2, 'name' => 'T2', 'features' => ['tasks']
        ]);

        TaskStatus::create([
            'public_id' => PublicIdGenerator::generate(),
            'slug' => 'global', 'name' => 'Global', 'tenant_id' => null
        ]);
        TaskStatus::create([
            'public_id' => PublicIdGenerator::generate(),
            'slug' => 't1', 'name' => 'Tenant One', 'tenant_id' => 1
        ]);
        TaskStatus::create([
            'public_id' => PublicIdGenerator::generate(),
            'slug' => 't2', 'name' => 'Tenant Two', 'tenant_id' => 2
        ]);

        $role = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Admin',
            'slug' => 'admin',
            'tenant_id' => 1,
            'abilities' => ['task_statuses.view'],
            'level' => 1,
        ]);

        $user = User::create([
            'public_id' => PublicIdGenerator::generate(),
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
        $data = $response->json('data');
        $names = collect($data)->pluck('name');
        $this->assertContains('Global', $names);
        $this->assertContains('Tenant One', $names);
        $this->assertNotContains('Tenant Two', $names);
        foreach ($data as $status) {
            $this->assertArrayHasKey('created_at', $status);
            $this->assertArrayHasKey('updated_at', $status);
        }
    }

    public function test_super_admin_sees_global_and_tenant_statuses_with_header(): void
    {
        Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'id' => 1, 'name' => 'T1', 'features' => ['tasks']
        ]);
        Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'id' => 2, 'name' => 'T2', 'features' => ['tasks']
        ]);

        TaskStatus::create([
            'public_id' => PublicIdGenerator::generate(),
            'slug' => 'global', 'name' => 'Global', 'tenant_id' => null
        ]);
        TaskStatus::create([
            'public_id' => PublicIdGenerator::generate(),
            'slug' => 't1', 'name' => 'Tenant One', 'tenant_id' => 1
        ]);
        TaskStatus::create([
            'public_id' => PublicIdGenerator::generate(),
            'slug' => 't2', 'name' => 'Tenant Two', 'tenant_id' => 2
        ]);

        $root = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'id' => 999, 'name' => 'Root'
        ]);
        $role = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'SuperAdmin',
            'slug' => 'super_admin',
            'tenant_id' => $root->id,
            'abilities' => ['*'],
            'level' => 0,
        ]);

        $user = User::create([
            'public_id' => PublicIdGenerator::generate(),
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
        $data = $response->json('data');
        $names = collect($data)->pluck('name');
        $this->assertContains('Global', $names);
        $this->assertContains('Tenant One', $names);
        $this->assertNotContains('Tenant Two', $names);
        foreach ($data as $status) {
            $this->assertArrayHasKey('created_at', $status);
            $this->assertArrayHasKey('updated_at', $status);
        }
    }
}