<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RoleLevelRestrictionTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_manage_role_above_level(): void
    {
        $tenant = Tenant::create(['name' => 'Tenant']);
        $adminRole = Role::create([
            'name' => 'Manager',
            'slug' => 'manager',
            'tenant_id' => $tenant->id,
            'level' => 2,
            'abilities' => ['roles.manage'],
        ]);
        $higherRole = Role::create([
            'name' => 'Supervisor',
            'slug' => 'supervisor',
            'tenant_id' => $tenant->id,
            'level' => 1,
        ]);

        $user = User::create([
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
}
