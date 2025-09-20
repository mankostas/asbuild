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

class SuperAdminCreatesTaskForTenantTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_task_for_selected_tenant(): void
    {
        $tenantA = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'A', 'features' => ['tasks']
        ]);
        $tenantB = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'B', 'features' => ['tasks']
        ]);

        $role = Role::create([
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
        $user->roles()->attach($role->id, ['tenant_id' => $tenantA->id]);
        Sanctum::actingAs($user);

        $response = $this->withHeader('X-Tenant-ID', $this->publicIdFor($tenantB))
            ->postJson('/api/tasks', [])
            ->assertStatus(201);

        $this->assertEquals($this->publicIdFor($tenantB), $response->json('data.tenant_id'));
    }
}
