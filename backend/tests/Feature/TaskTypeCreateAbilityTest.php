<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\TaskType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use App\Support\PublicIdGenerator;

class TaskTypeCreateAbilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_super_admin_cannot_create_task_type(): void
    {
        $tenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Tenant', 'features' => ['task_types']
        ]);
        $role = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'ClientAdmin',
            'slug' => 'client_admin',
            'tenant_id' => $tenant->id,
            'abilities' => ['task_types.view'],
            'level' => 1,
        ]);
        $user = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'User',
            'email' => 'user@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $user->roles()->attach($role->id, ['tenant_id' => $tenant->id]);
        Sanctum::actingAs($user);

        $payload = [
            'name' => 'Type',
            'schema_json' => json_encode(['sections' => []]),
            'statuses' => json_encode([]),
        ];

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/task-types', $payload)
            ->assertStatus(403);
    }

    public function test_super_admin_can_create_task_type(): void
    {
        $tenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Tenant', 'features' => ['task_types']
        ]);
        $role = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'SuperAdmin',
            'slug' => 'super_admin',
            'tenant_id' => null,
            'abilities' => ['task_types.create'],
            'level' => 0,
        ]);
        $user = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'User',
            'email' => 'user2@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $user->roles()->attach($role->id, ['tenant_id' => $tenant->id]);
        Sanctum::actingAs($user);

        $payload = [
            'name' => 'Type2',
            'schema_json' => json_encode(['sections' => []]),
            'statuses' => json_encode([]),
        ];

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/task-types', $payload)
            ->assertStatus(201);
    }

    public function test_cannot_view_task_type_from_another_tenant(): void
    {
        $tenantA = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Tenant A', 'features' => ['task_types']
        ]);
        $tenantB = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Tenant B', 'features' => ['task_types']
        ]);

        $role = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'ClientAdmin',
            'slug' => 'client_admin',
            'tenant_id' => $tenantA->id,
            'abilities' => ['task_types.view'],
            'level' => 1,
        ]);

        $user = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'User',
            'email' => 'viewer@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenantA->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $user->roles()->attach($role->id, ['tenant_id' => $tenantA->id]);
        Sanctum::actingAs($user);

        $typeA = TaskType::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'TypeA', 'tenant_id' => $tenantA->id
        ]);
        $typeB = TaskType::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'TypeB', 'tenant_id' => $tenantB->id
        ]);

        $this->withHeader('X-Tenant-ID', $tenantA->id)
            ->getJson("/api/task-types/{$typeB->id}")
            ->assertStatus(403);

        // ensure own type is accessible
        $this->withHeader('X-Tenant-ID', $tenantA->id)
            ->getJson("/api/task-types/{$typeA->id}")
            ->assertOk();
    }

    public function test_index_ignores_scope_and_tenant_id_for_non_super_admin(): void
    {
        $tenantA = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Tenant A', 'features' => ['task_types']
        ]);
        $tenantB = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Tenant B', 'features' => ['task_types']
        ]);

        $role = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'ClientAdmin',
            'slug' => 'client_admin',
            'tenant_id' => $tenantA->id,
            'abilities' => ['task_types.view'],
            'level' => 1,
        ]);

        $user = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'User',
            'email' => 'viewer2@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenantA->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $user->roles()->attach($role->id, ['tenant_id' => $tenantA->id]);
        Sanctum::actingAs($user);

        TaskType::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'TypeA', 'tenant_id' => $tenantA->id
        ]);
        TaskType::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'TypeB', 'tenant_id' => $tenantB->id
        ]);
        TaskType::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Global', 'tenant_id' => null
        ]);

        $this->withHeader('X-Tenant-ID', $tenantA->id)
            ->getJson('/api/task-types?scope=global')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonMissing(['name' => 'TypeB'])
            ->assertJsonMissing(['name' => 'Global'])
            ->assertJsonFragment(['name' => 'TypeA']);

        $this->withHeader('X-Tenant-ID', $tenantA->id)
            ->getJson('/api/task-types?tenant_id=' . $tenantB->id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonMissing(['name' => 'TypeB'])
            ->assertJsonFragment(['name' => 'TypeA']);
    }
}
