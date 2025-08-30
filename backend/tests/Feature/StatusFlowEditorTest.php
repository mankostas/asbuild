<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Models\TaskType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StatusFlowEditorTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_manage_ability_can_update_status_flow(): void
    {
        $tenant = Tenant::create(['name' => 'T', 'features' => ['tasks']]);
        $role = Role::create([
            'name' => 'ClientAdmin',
            'slug' => 'client_admin',
            'tenant_id' => $tenant->id,
            'abilities' => ['task_types.manage', 'task_type_versions.manage'],
            'level' => 1,
        ]);
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

        $type = TaskType::create([
            'name' => 'Type',
            'tenant_id' => $tenant->id,
            'schema_json' => ['sections' => []],
            'statuses' => ['draft' => []],
        ]);

        $payload = [
            'name' => 'Type',
            'statuses' => json_encode(['draft' => [], 'done' => []]),
            'status_flow_json' => json_encode([['draft', 'done']]),
        ];

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->patchJson("/api/task-types/{$type->id}", $payload)
            ->assertOk();

        $type->refresh();
        $this->assertEquals([['draft', 'done']], $type->status_flow_json);
    }

    public function test_user_without_manage_ability_cannot_update_status_flow(): void
    {
        $tenant = Tenant::create(['name' => 'T', 'features' => ['tasks']]);
        $role = Role::create([
            'name' => 'User',
            'slug' => 'user',
            'tenant_id' => $tenant->id,
            'abilities' => [],
            'level' => 1,
        ]);
        $user = User::create([
            'name' => 'U2',
            'email' => 'u2@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $user->roles()->attach($role->id, ['tenant_id' => $tenant->id]);
        Sanctum::actingAs($user);

        $type = TaskType::create([
            'name' => 'Type',
            'tenant_id' => $tenant->id,
            'schema_json' => ['sections' => []],
            'statuses' => ['draft' => []],
        ]);

        $payload = [
            'name' => 'Type',
            'statuses' => json_encode(['draft' => [], 'done' => []]),
            'status_flow_json' => json_encode([['draft', 'done']]),
        ];

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->patchJson("/api/task-types/{$type->id}", $payload)
            ->assertForbidden();
    }
}
