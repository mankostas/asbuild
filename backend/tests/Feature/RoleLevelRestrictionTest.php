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

class RoleLevelRestrictionTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_manage_role_above_level(): void
    {
        $tenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Tenant'
        ]);
        $adminRole = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Manager',
            'slug' => 'manager',
            'tenant_id' => $tenant->id,
            'level' => 2,
            'abilities' => ['roles.manage'],
        ]);
        $higherRole = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Supervisor',
            'slug' => 'supervisor',
            'tenant_id' => $tenant->id,
            'level' => 1,
        ]);

        $user = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $user->roles()->attach($adminRole->id, ['tenant_id' => $tenant->id]);
        Sanctum::actingAs($user);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->getJson('/api/roles')
            ->assertStatus(200)
            ->assertJsonMissing(['id' => $higherRole->id, 'name' => 'Supervisor']);

        $payload = ['name' => 'Boss', 'slug' => 'boss', 'level' => 1];
        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/roles', $payload)
            ->assertStatus(403);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->deleteJson("/api/roles/{$higherRole->id}")
            ->assertStatus(403);
    }

    public function test_index_scopes_roles_to_tenant_and_level(): void
    {
        $tenant1 = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Tenant1'
        ]);
        $tenant2 = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Tenant2'
        ]);

        $managerRole = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Manager',
            'slug' => 'manager',
            'tenant_id' => $tenant1->id,
            'level' => 2,
            'abilities' => ['roles.manage'],
        ]);

        $allowed1 = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Assistant', 'slug' => 'assistant', 'tenant_id' => $tenant1->id, 'level' => 2
        ]);
        $allowed2 = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Helper', 'slug' => 'helper', 'tenant_id' => $tenant1->id, 'level' => 3
        ]);
        $disallowed = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Supervisor', 'slug' => 'supervisor', 'tenant_id' => $tenant1->id, 'level' => 1
        ]);
        $otherTenant = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Other', 'slug' => 'other', 'tenant_id' => $tenant2->id, 'level' => 2
        ]);
        $global = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Global', 'slug' => 'global'
        ]);

        $user = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant1->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $user->roles()->attach($managerRole->id, ['tenant_id' => $tenant1->id]);
        Sanctum::actingAs($user);

        $response = $this->withHeader('X-Tenant-ID', $tenant1->id)
            ->getJson("/api/roles?tenant_id={$tenant2->id}")
            ->assertStatus(200);

        $ids = array_column($response->json('data'), 'id');

        $this->assertContains($allowed1->id, $ids);
        $this->assertContains($allowed2->id, $ids);
        $this->assertNotContains($disallowed->id, $ids);
        $this->assertNotContains($otherTenant->id, $ids);
        $this->assertNotContains($global->id, $ids);
    }
}
