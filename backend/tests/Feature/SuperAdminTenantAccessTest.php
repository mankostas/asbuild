<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SuperAdminTenantAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_roles_for_any_tenant(): void
    {
        $tenantA = Tenant::create(['name' => 'A']);
        $tenantB = Tenant::create(['name' => 'B']);

        $superRole = Role::create([
            'name' => 'SuperAdmin',
            'slug' => 'super_admin',
            'tenant_id' => $tenantA->id,
        ]);

        $user = User::create([
            'name' => 'SA',
            'email' => 'sa@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenantA->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $user->roles()->attach($superRole->id, ['tenant_id' => $tenantA->id]);

        $roleB = Role::create(['name' => 'Viewer', 'slug' => 'viewer', 'tenant_id' => $tenantB->id]);

        Sanctum::actingAs($user);

        $this->withHeader('X-Tenant-ID', $tenantB->id)
            ->getJson('/api/roles?tenant_id=' . $tenantB->id)
            ->assertStatus(200)
            ->assertJsonFragment(['id' => $roleB->id, 'tenant_id' => $tenantB->id]);
    }

    public function test_super_admin_can_update_role_for_any_tenant(): void
    {
        $tenantA = Tenant::create(['name' => 'A']);
        $tenantB = Tenant::create(['name' => 'B']);

        $superRole = Role::create([
            'name' => 'SuperAdmin',
            'slug' => 'super_admin',
            'tenant_id' => $tenantA->id,
        ]);

        $user = User::create([
            'name' => 'SA',
            'email' => 'sa@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenantA->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $user->roles()->attach($superRole->id, ['tenant_id' => $tenantA->id]);

        $roleB = Role::create(['name' => 'Viewer', 'slug' => 'viewer', 'tenant_id' => $tenantB->id]);

        Sanctum::actingAs($user);

        $this->withHeader('X-Tenant-ID', $tenantB->id)
            ->putJson("/api/roles/{$roleB->id}", [
                'name' => 'Updated',
                'slug' => 'updated',
            ])
            ->assertStatus(200)
            ->assertJsonFragment(['id' => $roleB->id, 'name' => 'Updated', 'slug' => 'updated']);

        $this->assertDatabaseHas('roles', [
            'id' => $roleB->id,
            'tenant_id' => $tenantB->id,
            'name' => 'Updated',
            'slug' => 'updated',
        ]);
    }
}
