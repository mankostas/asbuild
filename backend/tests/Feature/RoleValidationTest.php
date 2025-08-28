<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RoleValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_creation_rejects_unavailable_abilities(): void
    {
        $tenant = Tenant::create(['name' => 'Tenant', 'features' => ['appointments']]);
        $adminRole = Role::create([
            'name' => 'Admin',
            'slug' => 'admin',
            'tenant_id' => $tenant->id,
            'abilities' => ['roles.manage'],
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

        $payload = [
            'name' => 'Status Manager',
            'slug' => 'status_manager',
            'abilities' => ['statuses.manage'],
            'level' => 1,
        ];

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/roles', $payload)
            ->assertStatus(422);

        $tenant->update(['features' => ['appointments', 'statuses']]);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/roles', $payload)
            ->assertStatus(201)
            ->assertJsonFragment(['abilities' => ['statuses.manage']]);
    }
}
