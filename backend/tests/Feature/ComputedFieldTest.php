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

class ComputedFieldTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Tenant::create(['id' => 1, 'name' => 'T', 'features' => ['tasks']]);
        $role = Role::create([
            'name' => 'User',
            'slug' => 'user',
            'tenant_id' => 1,
            'abilities' => ['tasks.manage'],
            'level' => 1,
        ]);
        $user = User::create([
            'name' => 'U',
            'email' => 'u@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => 1,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $user->roles()->attach($role->id, ['tenant_id' => 1]);
        Sanctum::actingAs($user);
    }

    public function test_computed_field_recalculates(): void
    {
        $schema = [
            'sections' => [
                [
                    'key' => 'main',
                    'label' => 'Main',
                    'fields' => [
                        ['key' => 'a', 'label' => 'A', 'type' => 'number'],
                        ['key' => 'b', 'label' => 'B', 'type' => 'number'],
                        ['key' => 'total', 'label' => 'Total', 'type' => 'computed', 'expr' => 'a + b'],
                    ],
                ],
            ],
        ];
        $type = TaskType::create([
            'name' => 'T',
            'tenant_id' => 1,
            'schema_json' => $schema,
            'statuses' => ['draft' => []],
            'status_flow_json' => [['draft']],
        ]);
        $payload = [
            'task_type_id' => $type->id,
            'form_data' => ['a' => 2, 'b' => 3, 'total' => 99],
        ];
        $this->withHeader('X-Tenant-ID', 1)
            ->postJson('/api/tasks', $payload)
            ->assertStatus(201)
            ->assertJsonPath('data.form_data.total', 5);
    }
}
