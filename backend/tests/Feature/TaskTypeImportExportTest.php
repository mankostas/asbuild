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

class TaskTypeImportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_and_import_endpoints(): void
    {
        $tenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'T', 'features' => ['tasks']
        ]);
        $role = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'SuperAdmin',
            'slug' => 'super_admin',
            'tenant_id' => null,
            'abilities' => ['task_types.manage'],
            'level' => 0,
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

        $type = TaskType::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Type', 'tenant_id' => $tenant->id
        ]);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson("/api/task-types/{$type->public_id}/export")
            ->assertOk()
            ->assertJsonFragment(['name' => 'Type']);

        $payload = [
            'name' => 'Imported',
            'schema_json' => ['sections' => []],
        ];

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/task-types/import', $payload)
            ->assertStatus(201)
            ->assertJsonFragment(['name' => 'Imported']);
    }

    public function test_cannot_export_task_type_from_other_tenant(): void
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
            'name' => 'ClientAdmin',
            'slug' => 'client_admin',
            'tenant_id' => $tenantA->id,
            'abilities' => ['task_types.manage'],
            'level' => 1,
        ]);

        $user = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'U',
            'email' => 'u2@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenantA->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $user->roles()->attach($role->id, ['tenant_id' => $tenantA->id]);
        Sanctum::actingAs($user);

        $otherType = TaskType::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Other', 'tenant_id' => $tenantB->id
        ]);

        $this->withHeader('X-Tenant-ID', $tenantA->id)
            ->postJson("/api/task-types/{$otherType->public_id}/export")
            ->assertStatus(403);
    }
}
