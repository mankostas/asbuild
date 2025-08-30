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

class TaskSlaPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_policy_applies_sla_end_at(): void
    {
        $tenant = Tenant::create(['name' => 'T', 'features' => ['tasks']]);
        $role = Role::create([
            'name' => 'Admin',
            'slug' => 'admin',
            'tenant_id' => $tenant->id,
            'abilities' => ['task_sla_policies.manage', 'tasks.create', 'tasks.manage'],
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

        $policy = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson("/api/task-types/{$type->id}/sla-policies", [
                'priority' => 'high',
                'resolve_within_mins' => 120,
                'calendar_json' => [
                    'hours' => [
                        'wed' => ['09:00','17:00'],
                        'thu' => ['09:00','17:00'],
                    ],
                    'holidays' => [],
                ],
            ])->assertCreated()->json('data');

        $task = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/tasks', [
                'task_type_id' => $type->id,
                'priority' => 'high',
                'sla_start_at' => '2025-01-01T16:00:00Z',
                'form_data' => [],
            ])->assertCreated()->json('data');

        $this->assertEquals('2025-01-02T10:00:00.000000Z', $task['sla_end_at']);
    }
}
