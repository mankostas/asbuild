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

class TenantRoleAbilityRestrictionTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_features_limit_role_creation(): void
    {
        $tenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Tenant', 'features' => ['tasks']
        ]);

        $adminRole = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Manager',
            'slug' => 'manager',
            'tenant_id' => $tenant->id,
            'level' => 1,
            'abilities' => ['roles.manage'],
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

        $payload = [
            'name' => 'Type Manager',
            'slug' => 'type_manager',
            'abilities' => ['task_types.manage'],
            'level' => 1,
        ];

        $this->withHeader('X-Tenant-ID', $this->publicIdFor($tenant))
            ->postJson('/api/roles', $payload)
            ->assertStatus(422);

        $tenant->update(['features' => ['tasks', 'task_types']]);

        $this->withHeader('X-Tenant-ID', $this->publicIdFor($tenant))
            ->postJson('/api/roles', $payload)
            ->assertStatus(201)
            ->assertJsonFragment(['abilities' => ['task_types.manage']]);
    }
}

