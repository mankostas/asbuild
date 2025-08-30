<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskTypeBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_task_type_with_sections(): void
    {
        $tenant = Tenant::create(['name' => 'T', 'features' => ['tasks']]);
        $role = Role::create(['name' => 'Admin', 'slug' => 'admin', 'tenant_id' => $tenant->id, 'abilities' => ['task_types.manage'], 'level' => 1]);
        $user = User::create([
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
        $tenant = Tenant::create(['name' => 'T', 'features' => ['tasks']]);
        $role = Role::create(['name' => 'Admin', 'slug' => 'admin', 'tenant_id' => $tenant->id, 'abilities' => ['task_types.manage'], 'level' => 1]);
        $user = User::create([
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
        $tenant = Tenant::create(['name' => 'T', 'features' => ['tasks']]);
        $role = Role::create(['name' => 'Admin', 'slug' => 'admin', 'tenant_id' => $tenant->id, 'abilities' => ['task_types.manage'], 'level' => 1]);
        $user = User::create([
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

    public function test_builder_requires_auth(): void
    {
        $this->postJson('/api/task-types', [])
            ->assertUnauthorized();
    }
}
