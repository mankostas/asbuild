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

class TaskTypePreviewValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_validation_endpoint(): void
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
            'abilities' => ['task_types.manage'],
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

        $type = TaskType::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Type', 'tenant_id' => $tenant->id
        ]);

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
            ->postJson("/api/task-types/{$type->public_id}/validate", [
                'schema_json' => $schema,
                'form_data' => ['f1' => 'ok'],
            ])
            ->assertOk();

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson("/api/task-types/{$type->public_id}/validate", [
                'schema_json' => $schema,
                'form_data' => [],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['form_data.f1']);
    }
}
