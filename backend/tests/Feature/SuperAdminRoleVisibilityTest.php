<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SuperAdminRoleVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_roles_from_all_tenants_without_header(): void
    {
        $rootTenant = Tenant::create(['name' => 'Root']);
        $superRole = Role::create([
            'name' => 'SuperAdmin',
            'tenant_id' => $rootTenant->id,
            'level' => 0,
        ]);

        $superUser = User::create([
            'name' => 'Super',
            'email' => 'super@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $rootTenant->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $superUser->roles()->attach($superRole->id, ['tenant_id' => $rootTenant->id]);
        Sanctum::actingAs($superUser);

        $tenantA = Tenant::create(['name' => 'Tenant A']);
        $tenantB = Tenant::create(['name' => 'Tenant B']);
        Role::create(['name' => 'ClientAdmin', 'tenant_id' => $tenantA->id, 'level' => 1]);
        Role::create(['name' => 'ClientAdmin', 'tenant_id' => $tenantB->id, 'level' => 1]);

        $this->getJson('/api/roles')
            ->assertStatus(200)
            ->assertJsonCount(3);
    }
}
