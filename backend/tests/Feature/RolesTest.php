<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_assignment_persists(): void
    {
        $tenant = Tenant::create(['name' => 'Tenant']);
        $adminRole = Role::create([
            'name' => 'ClientAdmin',
            'slug' => 'client_admin',
            'tenant_id' => $tenant->id,
            'abilities' => ['roles.manage'],
        ]);
        $role = Role::create(['name' => 'Tester', 'slug' => 'tester', 'tenant_id' => $tenant->id]);

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $user = User::create([
            'name' => 'User',
            'email' => 'user@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $admin->roles()->attach($adminRole->id, ['tenant_id' => $tenant->id]);
        Sanctum::actingAs($admin);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson("/api/roles/{$role->id}/assign", ['user_id' => $user->id])
            ->assertStatus(200);

        $this->assertTrue(
            $user->roles()->where('roles.id', $role->id)->wherePivot('tenant_id', $tenant->id)->exists()
        );
    }

    public function test_super_admin_can_assign_any_ability(): void
    {
        $tenant = Tenant::create(['name' => 'Tenant', 'features' => ['tasks']]);
        $superRole = Role::create([
            'name' => 'SuperAdmin',
            'slug' => 'super_admin',
            'tenant_id' => null,
            'level' => 0,
            'abilities' => ['roles.manage'],
        ]);
        $user = User::create([
            'name' => 'Root',
            'email' => 'root@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $user->roles()->attach($superRole->id, ['tenant_id' => $tenant->id]);
        Sanctum::actingAs($user);

        $payload = [
            'name' => 'Types Manager',
            'slug' => 'task_types.manager',
            'abilities' => ['task_types.manage'],
            'tenant_id' => $tenant->id,
            'level' => 1,
        ];

        $this->postJson('/api/roles', $payload)
            ->assertStatus(201)
            ->assertJsonFragment(['abilities' => ['task_types.manage']]);
    }

    public function test_features_limit_assignable_abilities(): void
    {
        $tenant = Tenant::create(['name' => 'Tenant', 'features' => ['tasks']]);
        $adminRole = Role::create([
            'name' => 'Manager',
            'slug' => 'manager',
            'tenant_id' => $tenant->id,
            'level' => 1,
            'abilities' => ['roles.manage'],
        ]);
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin2@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $user->roles()->attach($adminRole->id, ['tenant_id' => $tenant->id]);
        Sanctum::actingAs($user);

        $payload = [
            'name' => 'Type Manager',
            'slug' => 'type_manager',
            'abilities' => ['task_types.manage'],
            'level' => 1,
        ];

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/roles', $payload)
            ->assertStatus(422);

        $tenant->update(['features' => ['tasks', 'task_types']]);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/roles', $payload)
            ->assertStatus(201)
            ->assertJsonFragment(['abilities' => ['task_types.manage']]);
    }
}
