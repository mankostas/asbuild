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

class TaskConditionalLogicTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $tenant = Tenant::create(['name' => 'T', 'features' => ['tasks']]);
        $role = Role::create([
            'name' => 'Admin',
            'slug' => 'admin',
            'tenant_id' => $tenant->id,
            'abilities' => ['tasks.manage'],
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
        $this->tenant = $tenant;
    }

    public function test_conditional_required_field(): void
    {
        $type = TaskType::create([
            'name' => 'Type',
            'tenant_id' => $this->tenant->id,
            'schema_json' => [
                'sections' => [[
                    'key' => 'main',
                    'label' => 'Main',
                    'fields' => [
                        ['key' => 'priority', 'label' => 'Priority', 'type' => 'select', 'enum' => ['low', 'high']],
                        ['key' => 'due_date', 'label' => 'Due Date', 'type' => 'date'],
                        ['key' => 'escalation_reason', 'label' => 'Escalation', 'type' => 'text'],
                    ],
                ]],
                'logic' => [[
                    'if' => ['field' => 'priority', 'eq' => 'high'],
                    'then' => [
                        ['require' => 'due_date'],
                        ['show' => 'escalation_reason'],
                    ],
                ]],
            ],
        ]);

        $payload = [
            'task_type_id' => $type->id,
            'form_data' => ['priority' => 'high'],
        ];
        $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson('/api/tasks', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['form_data.due_date']);

        $payload['form_data']['priority'] = 'low';
        $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson('/api/tasks', $payload)
            ->assertCreated();
    }
}
