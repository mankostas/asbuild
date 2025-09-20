<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use App\Support\PublicIdGenerator;

class SuperAdminTenantAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_roles_for_any_tenant(): void
    {
        $tenantA = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'A'
        ]);
        $tenantB = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'B'
        ]);

        $superRole = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'SuperAdmin',
            'slug' => 'super_admin',
            'tenant_id' => $tenantA->id,
        ]);

        $user = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'SA',
            'email' => 'sa@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenantA->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $user->roles()->attach($superRole->id, ['tenant_id' => $tenantA->id]);

        $roleB = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Viewer', 'slug' => 'viewer', 'tenant_id' => $tenantB->id
        ]);

        Sanctum::actingAs($user);

        $this->withHeader('X-Tenant-ID', $this->publicIdFor($tenantB))
            ->getJson('/api/roles?tenant_id=' . $this->publicIdFor($tenantB))
            ->assertStatus(200)
            ->assertJsonFragment([
                'id' => $this->publicIdFor($roleB),
                'tenant_id' => $this->publicIdFor($tenantB),
            ]);
    }

    public function test_super_admin_can_update_role_for_any_tenant(): void
    {
        $tenantA = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'A'
        ]);
        $tenantB = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'B'
        ]);

        $superRole = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'SuperAdmin',
            'slug' => 'super_admin',
            'tenant_id' => $tenantA->id,
        ]);

        $user = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'SA',
            'email' => 'sa@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenantA->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $user->roles()->attach($superRole->id, ['tenant_id' => $tenantA->id]);

        $roleB = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Viewer', 'slug' => 'viewer', 'tenant_id' => $tenantB->id
        ]);

        Sanctum::actingAs($user);

        $this->withHeader('X-Tenant-ID', $this->publicIdFor($tenantB))
            ->putJson("/api/roles/{$this->publicIdFor($roleB)}", [
                'name' => 'Updated',
                'slug' => 'updated',
            ])
            ->assertStatus(200)
            ->assertJsonFragment([
                'id' => $this->publicIdFor($roleB),
                'name' => 'Updated',
                'slug' => 'updated',
            ]);

        $this->assertDatabaseHas('roles', [
            'id' => $roleB->id,
            'tenant_id' => $tenantB->id,
            'name' => 'Updated',
            'slug' => 'updated',
        ]);
    }
}
