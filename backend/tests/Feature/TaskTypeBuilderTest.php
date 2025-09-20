<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\TaskType;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use App\Support\PublicIdGenerator;

class TaskTypeBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_task_type_with_sections(): void
    {
        $tenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'T', 'features' => ['tasks']
        ]);
        $role = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Admin',
            'slug' => 'admin',
            'tenant_id' => $tenant->id,
            'abilities' => ['task_types.create'],
            'level' => 1,
        ]);
        $user = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'U',
            'email' => 'u@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $user->roles()->attach($role->id, ['tenant_id' => $tenant->id]);
        Sanctum::actingAs($user);

        $payload = [
            'name' => 'Builder',
            'schema_json' => json_encode([
                'sections' => [[
                    'key' => 's1',
                    'label' => 'Section 1',
                    'fields' => [[
                        'key' => 'f1',
                        'label' => 'Field 1',
                        'type' => 'text',
                    ]],
                ]],
            ]),
            'statuses' => json_encode([[
                'key' => 'draft',
                'label' => 'Draft',
            ]]),
        ];

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/task-types', $payload)
            ->assertCreated()
            ->assertJsonPath('data.name', 'Builder');
    }

    public function test_store_task_type_with_abilities(): void
    {
        $tenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'T', 'features' => ['tasks']
        ]);
        $role = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Admin', 'slug' => 'admin', 'tenant_id' => $tenant->id, 'abilities' => ['task_types.create'], 'level' => 1
        ]);
        $user = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'U',
            'email' => 'u2@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $user->roles()->attach($role->id, ['tenant_id' => $tenant->id]);
        Sanctum::actingAs($user);

        $payload = [
            'name' => 'With Abilities',
            'schema_json' => json_encode(['sections' => []]),
            'statuses' => json_encode([]),
            'abilities_json' => json_encode(['read' => true, 'delete' => false]),
        ];

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/task-types', $payload)
            ->assertCreated()
            ->assertJsonPath('data.abilities_json.read', true)
            ->assertJsonPath('data.abilities_json.delete', false);
    }

    public function test_store_task_type_with_role_permissions(): void
    {
        $tenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'T', 'features' => ['tasks']
        ]);
        $role = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Admin',
            'slug' => 'admin',
            'tenant_id' => $tenant->id,
            'abilities' => ['task_types.create'],
            'level' => 1,
        ]);
        $user = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'U',
            'email' => 'u3@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $user->roles()->attach($role->id, ['tenant_id' => $tenant->id]);
        Sanctum::actingAs($user);

        $payload = [
            'name' => 'With Role Permissions',
            'schema_json' => json_encode(['sections' => []]),
            'statuses' => json_encode([]),
            'abilities_json' => json_encode([
                'admin' => [
                    'read' => true,
                    'edit' => false,
                    'delete' => false,
                    'export' => false,
                    'assign' => false,
                ],
            ]),
        ];

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/task-types', $payload)
            ->assertCreated()
            ->assertJsonPath('data.abilities_json.admin.read', true)
            ->assertJsonPath('data.abilities_json.admin.edit', false);
    }

    public function test_permissions_persist_on_edit(): void
    {
        $tenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'T', 'features' => ['tasks']
        ]);
        $role = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Admin',
            'slug' => 'admin',
            'tenant_id' => $tenant->id,
            'abilities' => ['task_types.create'],
            'level' => 1,
        ]);
        $user = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'U',
            'email' => 'u4@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $user->roles()->attach($role->id, ['tenant_id' => $tenant->id]);
        Sanctum::actingAs($user);

        $payload = [
            'name' => 'Permissions Persist',
            'schema_json' => json_encode(['sections' => []]),
            'statuses' => json_encode([]),
            'abilities_json' => json_encode([
                'admin' => [
                    'read' => true,
                    'edit' => false,
                    'delete' => false,
                    'export' => false,
                    'assign' => false,
                ],
            ]),
        ];

        $taskTypePublicId = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/task-types', $payload)
            ->assertCreated()
            ->json('data.id');

        $this->assertIsString($taskTypePublicId);

        $taskTypeId = $this->idFromPublicId(TaskType::class, $taskTypePublicId);
        $taskType = TaskType::query()->find($taskTypeId);
        $this->assertNotNull($taskType);
        $this->assertSame($taskTypeId, $taskType->getKey());
        $this->assertSame($taskTypePublicId, $taskType->public_id);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->getJson("/api/task-types/{$taskTypePublicId}")
            ->assertOk()
            ->assertJsonPath('data.abilities_json.admin.read', true)
            ->assertJsonPath('data.abilities_json.admin.edit', false);

        $update = [
            'abilities_json' => json_encode([
                'admin' => [
                    'read' => true,
                    'edit' => true,
                    'delete' => false,
                    'export' => false,
                    'assign' => false,
                ],
            ]),
        ];

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->patchJson("/api/task-types/{$taskTypePublicId}", $update)
            ->assertOk()
            ->assertJsonPath('data.abilities_json.admin.edit', true);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->getJson("/api/task-types/{$taskTypePublicId}")
            ->assertOk()
            ->assertJsonPath('data.abilities_json.admin.edit', true);
    }

    public function test_builder_requires_auth(): void
    {
        $this->postJson('/api/task-types', [])
            ->assertUnauthorized();
    }
}
