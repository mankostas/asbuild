<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\TaskStatus;
use App\Models\TaskType;

class TenantBootstrapSeeder extends Seeder
{
    public function run(): void
    {
        // Global role
        DB::table('roles')->updateOrInsert(
            ['tenant_id' => null, 'slug' => 'super_admin'],
            [
                'name' => 'Super Admin',
                'level' => 0,
                'abilities' => json_encode(['*']),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Tenant
        DB::table('tenants')->updateOrInsert(
            ['id' => 1],
            [
                'name' => 'Acme Vet',
                'quota_storage_mb' => 0,
                'features' => json_encode(['tasks', 'notifications', 'task_types', 'task_statuses', 'teams', 'themes']),
                'phone' => '555-123-4567',
                'address' => '1 Pet Street',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        $tenantId = DB::table('tenants')->where('id', 1)->value('id');

        // Tenant roles
        $tenantAbilities = \App\Models\Tenant::find($tenantId)->allowedAbilities();
        DB::table('roles')->updateOrInsert(
            ['tenant_id' => $tenantId, 'slug' => 'tenant'],
            [
                'name' => 'Tenant',
                'level' => 1,
                // Grant core abilities for tenant-level administration
                'abilities' => json_encode($tenantAbilities),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DefaultFeatureRolesSeeder::syncDefaultRolesForFeatures(\App\Models\Tenant::find($tenantId));

        $managerAbilities = array_intersect(
            ['tasks.manage', 'teams.manage', 'task_statuses.manage', 'task_types.manage'],
            $tenantAbilities
        );
        $agentAbilities = array_intersect(
            ['tasks.view', 'tasks.update', 'tasks.status.update'],
            $tenantAbilities
        );

        DB::table('roles')->updateOrInsert(
            ['tenant_id' => $tenantId, 'slug' => 'manager'],
            [
                'name' => 'Manager',
                'level' => 1,
                'abilities' => json_encode($managerAbilities),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        $managerRoleId = DB::table('roles')->where('tenant_id', $tenantId)->where('slug', 'manager')->value('id');

        DB::table('roles')->updateOrInsert(
            ['tenant_id' => $tenantId, 'slug' => 'agent'],
            [
                'name' => 'Agent',
                'level' => 2,
                'abilities' => json_encode($agentAbilities),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        $agentRoleId = DB::table('roles')->where('tenant_id', $tenantId)->where('slug', 'agent')->value('id');

        // Team
        DB::table('teams')->updateOrInsert(
            ['tenant_id' => $tenantId, 'name' => 'Front Desk'],
            [
                'description' => 'Reception and coordination',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        $teamId = DB::table('teams')->where('tenant_id', $tenantId)->where('name', 'Front Desk')->value('id');

        // Employees
        DB::table('users')->updateOrInsert(
            ['email' => 'manager@acme.test'],
            [
                'name' => 'Maggie Manager',
                'password' => Hash::make('password'),
                'tenant_id' => $tenantId,
                'phone' => '555-000-0001',
                'address' => '1 Pet Street',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        $managerId = DB::table('users')->where('email', 'manager@acme.test')->value('id');

        DB::table('users')->updateOrInsert(
            ['email' => 'agent@acme.test'],
            [
                'name' => 'Andy Agent',
                'password' => Hash::make('password'),
                'tenant_id' => $tenantId,
                'phone' => '555-000-0002',
                'address' => '2 Pet Street',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        $agentId = DB::table('users')->where('email', 'agent@acme.test')->value('id');

        // Assign roles to employees
        DB::table('role_user')->updateOrInsert(
            ['role_id' => $managerRoleId, 'user_id' => $managerId, 'tenant_id' => $tenantId],
            ['created_at' => now(), 'updated_at' => now()]
        );
        DB::table('role_user')->updateOrInsert(
            ['role_id' => $agentRoleId, 'user_id' => $agentId, 'tenant_id' => $tenantId],
            ['created_at' => now(), 'updated_at' => now()]
        );

        // Assign employees to team
        foreach ([$managerId, $agentId] as $employeeId) {
            DB::table('team_employee')->updateOrInsert(
                ['team_id' => $teamId, 'employee_id' => $employeeId],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        // Default task statuses
        $defaultStatuses = [
            ['slug' => 'todo', 'name' => 'To Do', 'color' => '#9ca3af'],
            ['slug' => 'in_progress', 'name' => 'In Progress', 'color' => '#3b82f6'],
            ['slug' => 'qa', 'name' => 'QA', 'color' => '#f59e0b'],
            ['slug' => 'done', 'name' => 'Done', 'color' => '#10b981'],
        ];

        foreach ($defaultStatuses as $index => $status) {
            DB::table('task_statuses')->updateOrInsert(
                ['tenant_id' => $tenantId, 'slug' => $status['slug']],
                [
                    'name' => $status['name'],
                    'color' => $status['color'],
                    'position' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // Example task type
        $schema = [
            'sections' => [
                [
                    'key' => 'details',
                    'label' => 'Details',
                    'fields' => [
                        ['key' => 'description', 'label' => 'Description', 'type' => 'text'],
                    ],
                ],
                [
                    'key' => 'photos',
                    'label' => 'Photos',
                    'fields' => [
                        ['key' => 'before_photo', 'label' => 'Before Photo', 'type' => 'photo'],
                        ['key' => 'after_photo', 'label' => 'After Photo', 'type' => 'photo'],
                    ],
                ],
            ],
        ];

        $typeStatuses = [
            'todo' => [],
            'in_progress' => [],
            'qa' => [],
            'done' => [],
        ];

        $transitions = [
            ['todo', 'in_progress'],
            ['in_progress', 'qa'],
            ['qa', 'done'],
        ];

        DB::table('task_types')->updateOrInsert(
            ['tenant_id' => $tenantId, 'name' => 'General Task'],
            [
                'schema_json' => json_encode($schema),
                'statuses' => json_encode($typeStatuses),
                'status_flow_json' => json_encode($transitions),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
