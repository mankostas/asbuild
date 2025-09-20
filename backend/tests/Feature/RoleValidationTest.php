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

class RoleValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_creation_rejects_unavailable_abilities(): void
    {
        $tenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Tenant', 'features' => ['tasks']
        ]);
        $adminRole = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Admin',
            'slug' => 'admin',
            'tenant_id' => $tenant->id,
            'abilities' => ['roles.manage'],
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

        $payload = [
            'name' => 'Status Manager',
            'slug' => 'status_manager',
            'abilities' => ['task_statuses.manage'],
            'level' => 1,
        ];

        $this->withHeader('X-Tenant-ID', $this->publicIdFor($tenant))
            ->postJson('/api/roles', $payload)
            ->assertStatus(422);

        $tenant->update(['features' => ['tasks', 'task_statuses']]);

        $this->withHeader('X-Tenant-ID', $this->publicIdFor($tenant))
            ->postJson('/api/roles', $payload)
            ->assertStatus(201)
            ->assertJsonFragment(['abilities' => ['task_statuses.manage']]);
    }
}
