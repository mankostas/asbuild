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

class TenantScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_bypasses_tenant_scope(): void
    {
        $tenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'One'
        ]);
        $role = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'SuperAdmin', 'slug' => 'super_admin', 'tenant_id' => $tenant->id
        ]);
        $user = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'SA',
            'email' => 'sa@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $user->roles()->attach($role->id, ['tenant_id' => $tenant->id]);
        Sanctum::actingAs($user);

        $this->getJson('/api/roles')->assertStatus(200);
    }

    public function test_tenant_isolation_enforced(): void
    {
        $tenant1 = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'One'
        ]);
        $tenant2 = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Two'
        ]);
        $role = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'ClientAdmin',
            'slug' => 'client_admin',
            'tenant_id' => $tenant1->id,
            'abilities' => ['roles.manage'],
        ]);
        $user = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'User',
            'email' => 'user@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant1->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $user->roles()->attach($role->id, ['tenant_id' => $tenant1->id]);
        Sanctum::actingAs($user);

        $this->withHeader('X-Tenant-ID', $tenant2->id)
            ->getJson('/api/roles')
            ->assertStatus(403);
    }
}
