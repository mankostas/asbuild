<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RoleLevelTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $tenant = Tenant::create(['name' => 'Test Tenant']);
        $role = Role::create(['name' => 'ClientAdmin', 'slug' => 'client_admin', 'tenant_id' => $tenant->id]);
        $user = User::create([
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $user->roles()->attach($role->id, ['tenant_id' => $tenant->id]);
        Sanctum::actingAs($user);
        $this->tenant = $tenant;
    }

    public function test_can_create_role_with_custom_level(): void
    {
        $payload = ['name' => 'Support', 'slug' => 'support', 'level' => 5];
        $roleId = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson('/api/roles', $payload)
            ->assertStatus(201)
            ->assertJsonPath('data.level', 5)
            ->json('data.id');

        $this->assertDatabaseHas('roles', [
            'id' => $roleId,
            'level' => 5,
        ]);
    }

    public function test_can_update_role_level(): void
    {
        $role = Role::create([
            'name' => 'Agent',
            'slug' => 'agent',
            'tenant_id' => $this->tenant->id,
            'level' => 1,
        ]);

        $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->patchJson("/api/roles/{$role->id}", ['name' => 'Agent', 'slug' => 'agent', 'level' => 4])
            ->assertStatus(200)
            ->assertJsonPath('data.level', 4);

        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'level' => 4,
        ]);
    }
}

