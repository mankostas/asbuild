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

class RoleLevelTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $tenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Test Tenant'
        ]);
        $role = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'ClientAdmin',
            'slug' => 'client_admin',
            'tenant_id' => $tenant->id,
            'abilities' => ['roles.manage'],
        ]);
        $user = User::create([
            'public_id' => PublicIdGenerator::generate(),
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
        $rolePublicId = $this->withHeader('X-Tenant-ID', $this->publicIdFor($this->tenant))
            ->postJson('/api/roles', $payload)
            ->assertStatus(201)
            ->assertJsonPath('data.level', 5)
            ->json('data.id');

        $this->assertIsString($rolePublicId);

        $roleId = $this->idFromPublicId(Role::class, $rolePublicId);
        $role = Role::query()->find($roleId);
        $this->assertNotNull($role);
        $this->assertSame($roleId, $role->getKey());
        $this->assertSame($rolePublicId, $role->public_id);

        $this->assertDatabaseHas('roles', [
            'id' => $roleId,
            'level' => 5,
        ]);
    }

    public function test_can_update_role_level(): void
    {
        $role = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Agent',
            'slug' => 'agent',
            'tenant_id' => $this->tenant->id,
            'level' => 1,
        ]);

        $this->withHeader('X-Tenant-ID', $this->publicIdFor($this->tenant))
            ->patchJson("/api/roles/{$role->public_id}", ['name' => 'Agent', 'slug' => 'agent', 'level' => 4])
            ->assertStatus(200)
            ->assertJsonPath('data.level', 4);

        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'level' => 4,
        ]);
    }
}

