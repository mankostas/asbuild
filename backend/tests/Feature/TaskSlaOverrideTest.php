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

class TaskSlaOverrideTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Tenant::create(['id' => 1, 'name' => 'T', 'features' => ['tasks']]);
    }

    protected function makeType(): TaskType
    {
        if (! User::find(1)) {
            User::create([
                'id' => 1,
                'name' => 'Seeder',
                'email' => 'seed@example.com',
                'password' => Hash::make('secret'),
                'tenant_id' => 1,
                'phone' => '123',
                'address' => 'Street',
            ]);
        }

        return TaskType::create([
            'name' => 'Type',
            'tenant_id' => 1,
            'schema_json' => ['sections' => []],
            'statuses' => [['slug' => 'draft']],
            'status_flow_json' => [],
        ]);
    }

    protected function makeUser(array $abilities): User
    {
        $role = Role::create([
            'name' => 'R',
            'slug' => 'r',
            'tenant_id' => 1,
            'abilities' => $abilities,
            'level' => 1,
        ]);
        $user = User::create([
            'name' => 'U',
            'email' => uniqid() . '@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => 1,
            'phone' => '123',
            'address' => 'Street',
        ]);
        $user->roles()->attach($role->id, ['tenant_id' => 1]);
        Sanctum::actingAs($user);
        return $user;
    }

    public function test_override_fields_ignored_without_ability_on_create(): void
    {
        $this->makeType();
        $this->makeUser(['tasks.create']);

        $response = $this->withHeader('X-Tenant-ID', 1)
            ->postJson('/api/tasks', [
                'task_type_id' => 1,
                'sla_start_at' => '2025-01-01T08:00:00Z',
                'sla_end_at' => '2025-01-01T17:00:00Z',
            ]);

        $response->assertCreated();
        $this->assertNull($response->json('data.sla_start_at'));
        $this->assertNull($response->json('data.sla_end_at'));
    }

    public function test_override_fields_respected_with_ability_on_create(): void
    {
        $this->makeType();
        $this->makeUser(['tasks.create', 'tasks.sla.override']);

        $response = $this->withHeader('X-Tenant-ID', 1)
            ->postJson('/api/tasks', [
                'task_type_id' => 1,
                'sla_start_at' => '2025-01-01T08:00:00Z',
                'sla_end_at' => '2025-01-01T17:00:00Z',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.sla_start_at', '2025-01-01T08:00:00.000000Z')
            ->assertJsonPath('data.sla_end_at', '2025-01-01T17:00:00.000000Z');
    }
}

