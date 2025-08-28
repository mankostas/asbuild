<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TenantScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_bypasses_tenant_scope(): void
    {
        $tenant = Tenant::create(['name' => 'One']);
        $role = Role::create(['name' => 'SuperAdmin', 'slug' => 'super_admin', 'tenant_id' => $tenant->id]);
        $user = User::create([
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
        $tenant1 = Tenant::create(['name' => 'One']);
        $tenant2 = Tenant::create(['name' => 'Two']);
        $role = Role::create([
            'name' => 'ClientAdmin',
            'slug' => 'client_admin',
            'tenant_id' => $tenant1->id,
            'abilities' => ['roles.manage'],
        ]);
        $user = User::create([
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
