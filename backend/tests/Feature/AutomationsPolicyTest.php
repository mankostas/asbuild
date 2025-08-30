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

class AutomationsPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_manage_ability_required_for_automation_routes(): void
    {
        $tenant = Tenant::create(['name' => 'T', 'features' => ['tasks']]);
        $type = TaskType::create([
            'name' => 'Type',
            'tenant_id' => $tenant->id,
            'schema_json' => ['sections' => []],
            'statuses' => ['draft' => []],
        ]);

        $managerRole = Role::create([
            'name' => 'Manager',
            'slug' => 'manager',
            'tenant_id' => $tenant->id,
            'abilities' => ['task_automations.manage'],
            'level' => 1,
        ]);
        $viewerRole = Role::create([
            'name' => 'Viewer',
            'slug' => 'viewer',
            'tenant_id' => $tenant->id,
            'abilities' => ['task_automations.view'],
            'level' => 1,
        ]);

        $manager = User::create([
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
            ->getJson("/api/task-types/{$type->id}/automations")
            ->assertOk();

        $viewer = User::create([
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
            ->getJson("/api/task-types/{$type->id}/automations")
            ->assertForbidden();
    }
}
