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

class TaskFieldValidationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $user;

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
        $this->user = $user;
    }

    public function test_unique_validation_enforced(): void
    {
        $type = TaskType::create([
            'name' => 'Type',
            'tenant_id' => $this->tenant->id,
            'schema_json' => [
                'sections' => [[
                    'key' => 's1',
                    'label' => 'S1',
                    'fields' => [[
                        'key' => 'serial',
                        'label' => 'Serial',
                        'type' => 'text',
                        'validations' => ['unique' => true, 'regex' => '^\\d+$'],
                    ]],
                ]],
            ],
        ]);

        $payload = [
            'task_type_id' => $type->id,
            'form_data' => ['serial' => '123'],
        ];

        $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson('/api/tasks', $payload)
            ->assertCreated();

        $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson('/api/tasks', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['form_data.serial']);
    }

    public function test_regex_validation_fails(): void
    {
        $type = TaskType::create([
            'name' => 'Type',
            'tenant_id' => $this->tenant->id,
            'schema_json' => [
                'sections' => [[
                    'key' => 's1',
                    'label' => 'S1',
                    'fields' => [[
                        'key' => 'serial',
                        'label' => 'Serial',
                        'type' => 'text',
                        'validations' => ['regex' => '^\\d+$'],
                    ]],
                ]],
            ],
        ]);

        $payload = [
            'task_type_id' => $type->id,
            'form_data' => ['serial' => 'abc'],
        ];

        $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson('/api/tasks', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['form_data.serial']);
    }
}
