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

class ComputedFieldTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'id' => 1, 'name' => 'T', 'features' => ['tasks']
        ]);
        $role = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'User',
            'slug' => 'user',
            'tenant_id' => $this->tenant->id,
            'abilities' => ['tasks.manage'],
            'level' => 1,
        ]);
        $user = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'U',
            'email' => 'u@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $this->tenant->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $user->roles()->attach($role->id, ['tenant_id' => $this->tenant->id]);
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
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'T',
            'tenant_id' => $this->tenant->id,
            'schema_json' => $schema,
            'statuses' => ['draft' => []],
            'status_flow_json' => [['draft']],
        ]);
        $payload = [
            'task_type_id' => $type->public_id,
            'form_data' => ['a' => 2, 'b' => 3, 'total' => 99],
        ];
        $this->withHeader('X-Tenant-ID', $this->publicIdFor($this->tenant))
            ->postJson('/api/tasks', $payload)
            ->assertStatus(201)
            ->assertJsonPath('data.form_data.total', 5);
    }
}
