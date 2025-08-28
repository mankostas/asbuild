<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RoleRoutesTest extends TestCase
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

    public function test_crud_routes_work(): void
    {
        $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->getJson('/api/roles')
            ->assertStatus(200);

        $payload = ['name' => 'Tester', 'slug' => 'tester', 'level' => 1];
        $roleId = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson('/api/roles', $payload)
            ->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'name', 'slug', 'abilities', 'tenant_id', 'level'],
            ])
            ->assertJsonPath('data.level', 1)
            ->assertJsonPath('data.tenant_id', $this->tenant->id)
            ->assertJsonMissingPath('data.created_at')
            ->assertJsonMissingPath('data.updated_at')
            ->json('data.id');

        $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->getJson("/api/roles/{$roleId}")
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['id', 'name', 'slug', 'abilities', 'tenant_id', 'level'],
            ])
            ->assertJsonMissingPath('data.created_at')
            ->assertJsonMissingPath('data.updated_at');

        $update = ['name' => 'Updated', 'slug' => 'updated', 'level' => 2];
        $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->putJson("/api/roles/{$roleId}", $update)
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['id', 'name', 'slug', 'abilities', 'tenant_id', 'level'],
            ])
            ->assertJsonPath('data.name', 'Updated')
            ->assertJsonPath('data.slug', 'updated')
            ->assertJsonPath('data.level', 2)
            ->assertJsonMissingPath('data.created_at')
            ->assertJsonMissingPath('data.updated_at');

        $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->deleteJson("/api/roles/{$roleId}")
            ->assertStatus(200);
    }
}

