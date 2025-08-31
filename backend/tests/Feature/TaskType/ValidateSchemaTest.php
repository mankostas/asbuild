<?php

namespace Tests\Feature\TaskType;

use App\Models\{Tenant, Role, User, TaskType, TaskTypeVersion};
use App\Services\StatusFlowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ValidateSchemaTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(array $abilities = []): array
    {
        $tenant = Tenant::create(['name' => 'T', 'features' => ['tasks']]);
        $role = Role::create([
            'name' => 'ClientAdmin',
            'slug' => 'client_admin',
            'tenant_id' => $tenant->id,
            'abilities' => $abilities,
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
        return [$tenant, $user];
    }

    public function test_create_validate_endpoint(): void
    {
        [$tenant, $user] = $this->createUser(['task_types.create']);
        Sanctum::actingAs($user);

        $schema = [
            'sections' => [[
                'key' => 's1',
                'label' => 'S1',
                'fields' => [[
                    'key' => 'f1',
                    'label' => 'F1',
                    'type' => 'text',
                    'validations' => ['required' => true],
                ]],
            ]],
        ];

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/task-types/validate', [
                'schema_json' => $schema,
                'form_data' => ['f1' => 'ok'],
            ])
            ->assertOk();

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/task-types/validate', [
                'schema_json' => $schema,
                'form_data' => [],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['form_data.f1']);
    }

    public function test_edit_validate_endpoint(): void
    {
        [$tenant, $user] = $this->createUser(['task_types.manage']);
        Sanctum::actingAs($user);
        $type = TaskType::create(['name' => 'Type', 'tenant_id' => $tenant->id]);

        $schema = [
            'sections' => [[
                'key' => 's1',
                'label' => 'S1',
                'fields' => [[
                    'key' => 'f1',
                    'label' => 'F1',
                    'type' => 'text',
                    'validations' => ['required' => true],
                ]],
            ]],
        ];

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson("/api/task-types/{$type->id}/validate", [
                'schema_json' => $schema,
                'form_data' => ['f1' => 'ok'],
            ])
            ->assertOk();

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson("/api/task-types/{$type->id}/validate", [
                'schema_json' => $schema,
                'form_data' => [],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['form_data.f1']);
    }

    public function test_rbac_for_validate_endpoint(): void
    {
        [$tenant, $user] = $this->createUser([]);
        Sanctum::actingAs($user);

        $schema = ['sections' => []];

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/task-types/validate', [
                'schema_json' => $schema,
                'form_data' => [],
            ])
            ->assertStatus(403);
    }

    public function test_task_type_version_round_trip_preserves_status_flow(): void
    {
        [$tenant, $user] = $this->createUser(['task_types.manage']);
        $type = TaskType::create(['name' => 'Type', 'tenant_id' => $tenant->id]);

        $flow = [
            ['draft', 'review'],
            ['review', 'done'],
        ];

        $version = TaskTypeVersion::create([
            'task_type_id' => $type->id,
            'semver' => '1.0.0',
            'schema_json' => [],
            'status_flow_json' => $flow,
            'created_by' => $user->id,
        ]);

        $reloaded = TaskTypeVersion::find($version->id);
        $service = new StatusFlowService();

        $this->assertSame($flow, $reloaded->status_flow_json);
        $this->assertTrue($service->canTransition('draft', 'review', $reloaded));
        $this->assertTrue($service->canTransition('review', 'done', $reloaded));
    }
}
