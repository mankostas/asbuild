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

class SuperAdminRoleVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_roles_from_all_tenants_without_header(): void
    {
        $rootTenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Root'
        ]);
        $superRole = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'SuperAdmin',
            'tenant_id' => $rootTenant->id,
            'level' => 0,
        ]);

        $superUser = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Super',
            'email' => 'super@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $rootTenant->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $superUser->roles()->attach($superRole->id, ['tenant_id' => $rootTenant->id]);
        Sanctum::actingAs($superUser);

        $tenantA = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Tenant A'
        ]);
        $tenantB = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Tenant B'
        ]);
        Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'ClientAdmin', 'tenant_id' => $tenantA->id, 'level' => 1
        ]);
        Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'ClientAdmin', 'tenant_id' => $tenantB->id, 'level' => 1
        ]);

        $response = $this->getJson('/api/roles')
            ->assertStatus(200);

        $response->assertJsonFragment(['tenant_id' => $tenantA->id, 'name' => 'ClientAdmin']);
        $response->assertJsonFragment(['tenant_id' => $tenantB->id, 'name' => 'ClientAdmin']);
    }
}
