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

class RoleRoutesTest extends TestCase
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

    public function test_crud_routes_work(): void
    {
        $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->getJson('/api/roles')
            ->assertStatus(200)
            ->assertJsonPath('data.0.abilities.0', 'roles.manage');

        $payload = ['name' => 'Tester', 'slug' => 'tester', 'level' => 1, 'description' => 'Test role'];
        $rolePublicId = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson('/api/roles', $payload)
            ->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'name', 'description', 'slug', 'abilities', 'tenant_id', 'level'],
            ])
            ->assertJsonPath('data.level', 1)
            ->assertJsonPath('data.description', 'Test role')
            ->assertJsonPath('data.tenant_id', $this->publicIdFor($this->tenant))
            ->assertJsonMissingPath('data.created_at')
            ->assertJsonMissingPath('data.updated_at')
            ->json('data.id');

        $this->assertIsString($rolePublicId);

        $roleId = $this->idFromPublicId(Role::class, $rolePublicId);
        $role = Role::query()->find($roleId);
        $this->assertNotNull($role);
        $this->assertSame($roleId, $role->getKey());
        $this->assertSame($rolePublicId, $role->public_id);

        $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->getJson("/api/roles/{$rolePublicId}")
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['id', 'name', 'description', 'slug', 'abilities', 'tenant_id', 'level'],
            ])
            ->assertJsonPath('data.description', 'Test role')
            ->assertJsonMissingPath('data.created_at')
            ->assertJsonMissingPath('data.updated_at');

        $update = ['name' => 'Updated', 'slug' => 'updated', 'level' => 2, 'description' => 'Updated role'];
        $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->putJson("/api/roles/{$rolePublicId}", $update)
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['id', 'name', 'description', 'slug', 'abilities', 'tenant_id', 'level'],
            ])
            ->assertJsonPath('data.name', 'Updated')
            ->assertJsonPath('data.slug', 'updated')
            ->assertJsonPath('data.level', 2)
            ->assertJsonPath('data.description', 'Updated role')
            ->assertJsonMissingPath('data.created_at')
            ->assertJsonMissingPath('data.updated_at');

        $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->deleteJson("/api/roles/{$rolePublicId}")
            ->assertStatus(200);
    }

    public function test_super_admin_filters_roles_by_tenant(): void
    {
        $tenantA = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Tenant A'
        ]);
        $tenantB = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Tenant B'
        ]);

        Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Global', 'slug' => 'global'
        ]);
        Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Role A', 'slug' => 'role_a', 'tenant_id' => $tenantA->id
        ]);
        $roleB = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Role B', 'slug' => 'role_b', 'tenant_id' => $tenantB->id
        ]);

        $superRole = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'SuperAdmin',
            'slug' => 'super_admin',
            'tenant_id' => $tenantA->id,
            'abilities' => ['*'],
            'level' => 0,
        ]);

        $user = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Super',
            'email' => 'super@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenantA->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $user->roles()->attach($superRole->id, ['tenant_id' => $tenantA->id]);
        Sanctum::actingAs($user);

        $response = $this->getJson("/api/roles?tenant_id={$this->publicIdFor($tenantB)}")
            ->assertStatus(200)
            ->assertJsonMissing(['name' => 'Global'])
            ->assertJsonMissing(['name' => 'Role A'])
            ->assertJsonFragment([
                'id' => $this->publicIdFor($roleB),
                'tenant_id' => $this->publicIdFor($tenantB),
            ]);

        foreach ($response->json('data') as $role) {
            $this->assertSame($this->publicIdFor($tenantB), $role['tenant_id']);
        }
    }
}

