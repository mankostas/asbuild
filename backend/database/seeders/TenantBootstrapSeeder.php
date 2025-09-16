<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Support\TenantDefaults;
use App\Services\StatusFlowService;

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
        $defaultFeatures = [
            'dashboard',
            'tasks',
            'notifications',
            'task_types',
            'task_statuses',
            'teams',
            'themes',
            'billing',
        ];
        $defaultFeatures = array_values(array_intersect($defaultFeatures, config('features', [])));

        DB::table('tenants')->updateOrInsert(
            ['id' => 1],
            [
                'name' => 'Acme Vet',
                'quota_storage_mb' => 0,
                'features' => json_encode($defaultFeatures),
                'phone' => '555-123-4567',
                'address' => '1 Pet Street',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        $tenantId = DB::table('tenants')->where('id', 1)->value('id');

        // Tenant roles
        $tenant = \App\Models\Tenant::find($tenantId);
        $tenantAbilities = $tenant->allowedAbilities();
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

        DefaultFeatureRolesSeeder::syncDefaultRolesForFeatures($tenant, $tenant->selectedFeatureAbilities());

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
                'type' => 'employee',
                'status' => 'active',
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
                'type' => 'employee',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        $agentId = DB::table('users')->where('email', 'agent@acme.test')->value('id');

        // Assign existing feature roles to employees
        $managerRoleIds = DB::table('roles')
            ->where('tenant_id', $tenantId)
            ->where(function ($q) {
                $q->where('slug', 'tenant')
                    ->orWhere('slug', 'like', '%_manager');
            })
            ->pluck('id');

        foreach ($managerRoleIds as $roleId) {
            DB::table('role_user')->updateOrInsert(
                ['role_id' => $roleId, 'user_id' => $managerId, 'tenant_id' => $tenantId],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        $agentRoleIds = DB::table('roles')
            ->where('tenant_id', $tenantId)
            ->where('slug', 'like', '%_editor')
            ->pluck('id');

        foreach ($agentRoleIds as $roleId) {
            DB::table('role_user')->updateOrInsert(
                ['role_id' => $roleId, 'user_id' => $agentId, 'tenant_id' => $tenantId],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        // Assign employees to team
        foreach ([$managerId, $agentId] as $employeeId) {
            DB::table('team_employee')->updateOrInsert(
                ['team_id' => $teamId, 'employee_id' => $employeeId],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        // Default task statuses
        $defaultStatuses = TenantDefaults::TASK_STATUSES;

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

        $typeStatuses = array_fill_keys(array_column($defaultStatuses, 'slug'), []);

        $transitions = [];
        foreach (StatusFlowService::DEFAULT_TRANSITIONS as $from => $tos) {
            foreach ($tos as $to) {
                $transitions[] = [$from, $to];
            }
        }

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
