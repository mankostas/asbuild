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
use App\Support\PublicIdGenerator;

class TaskSlaPolicyAbilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_manage_ability_required_for_sla_policy_routes(): void
    {
        $tenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'T', 'features' => ['tasks']
        ]);
        $type = TaskType::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Type',
            'tenant_id' => $tenant->id,
            'schema_json' => ['sections' => []],
            'statuses' => ['draft' => []],
        ]);

        $managerRole = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Manager',
            'slug' => 'manager',
            'tenant_id' => $tenant->id,
            'abilities' => ['task_sla_policies.manage'],
            'level' => 1,
        ]);
        $viewerRole = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Viewer',
            'slug' => 'viewer',
            'tenant_id' => $tenant->id,
            'abilities' => ['task_sla_policies.view'],
            'level' => 1,
        ]);

        $manager = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'M',
            'email' => 'm@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $manager->roles()->attach($managerRole->id, ['tenant_id' => $tenant->id]);

        Sanctum::actingAs($manager);
        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->getJson("/api/task-types/{$type->public_id}/sla-policies")
            ->assertOk();

        $viewer = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'V',
            'email' => 'v@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $viewer->roles()->attach($viewerRole->id, ['tenant_id' => $tenant->id]);

        Sanctum::actingAs($viewer);
        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->getJson("/api/task-types/{$type->public_id}/sla-policies")
            ->assertForbidden();
    }
}
