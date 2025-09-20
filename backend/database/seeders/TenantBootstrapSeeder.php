<?php

namespace Database\Seeders;

use App\Services\StatusFlowService;
use App\Support\PublicIdGenerator;
use App\Support\TenantDefaults;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TenantBootstrapSeeder extends Seeder
{
    public function run(): void
    {
        // Global role
        $globalSuperAdmin = DB::table('roles')
            ->whereNull('tenant_id')
            ->where('slug', 'super_admin')
            ->first();

        $globalSuperAdminData = [
            'name' => 'Super Admin',
            'level' => 0,
            'abilities' => json_encode(['*']),
            'created_at' => $globalSuperAdmin->created_at ?? now(),
            'updated_at' => now(),
        ];

        if (! $globalSuperAdmin) {
            $globalSuperAdminData['public_id'] = PublicIdGenerator::generate();
        }

        DB::table('roles')->updateOrInsert(
            ['tenant_id' => null, 'slug' => 'super_admin'],
            $globalSuperAdminData
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
        ];
        $defaultFeatures = array_values(array_intersect($defaultFeatures, config('features', [])));

        $bootstrapTenant = DB::table('tenants')->where('id', 1)->first();

        $tenantData = [
            'name' => 'Acme Vet',
            'quota_storage_mb' => 0,
            'features' => json_encode($defaultFeatures),
            'phone' => '555-123-4567',
            'address' => '1 Pet Street',
            'archived_at' => null,
            'deleted_at' => null,
            'created_at' => $bootstrapTenant->created_at ?? now(),
            'updated_at' => now(),
        ];

        if (! $bootstrapTenant) {
            $tenantData['public_id'] = PublicIdGenerator::generate();
        }

        DB::table('tenants')->updateOrInsert(
            ['id' => 1],
            $tenantData
        );
        $tenantId = DB::table('tenants')->where('id', 1)->value('id');

        // Tenant roles
        $tenant = \App\Models\Tenant::find($tenantId);
        $tenantAbilities = $tenant->allowedAbilities();
        $tenantRole = DB::table('roles')
            ->where('tenant_id', $tenantId)
            ->where('slug', 'tenant')
            ->first();

        $tenantRoleData = [
            'name' => 'Tenant',
            'level' => 1,
            // Grant core abilities for tenant-level administration
            'abilities' => json_encode($tenantAbilities),
            'created_at' => $tenantRole->created_at ?? now(),
            'updated_at' => now(),
        ];

        if (! $tenantRole) {
            $tenantRoleData['public_id'] = PublicIdGenerator::generate();
        }

        DB::table('roles')->updateOrInsert(
            ['tenant_id' => $tenantId, 'slug' => 'tenant'],
            $tenantRoleData
        );

        DefaultFeatureRolesSeeder::syncDefaultRolesForFeatures($tenant, $tenant->selectedFeatureAbilities());

        // Team
        $frontDesk = DB::table('teams')
            ->where('tenant_id', $tenantId)
            ->where('name', 'Front Desk')
            ->first();

        $teamData = [
            'description' => 'Reception and coordination',
            'created_at' => $frontDesk->created_at ?? now(),
            'updated_at' => now(),
        ];

        if (! $frontDesk) {
            $teamData['public_id'] = PublicIdGenerator::generate();
        }

        DB::table('teams')->updateOrInsert(
            ['tenant_id' => $tenantId, 'name' => 'Front Desk'],
            $teamData
        );
        $teamId = DB::table('teams')->where('tenant_id', $tenantId)->where('name', 'Front Desk')->value('id');

        // Employees
        $manager = DB::table('users')->where('email', 'manager@acme.test')->first();

        $managerData = [
            'name' => 'Maggie Manager',
            'password' => Hash::make('password'),
            'tenant_id' => $tenantId,
            'phone' => '555-000-0001',
            'address' => '1 Pet Street',
            'type' => 'employee',
            'status' => 'active',
            'created_at' => $manager->created_at ?? now(),
            'updated_at' => now(),
        ];

        if (! $manager) {
            $managerData['public_id'] = PublicIdGenerator::generate();
        }

        DB::table('users')->updateOrInsert(
            ['email' => 'manager@acme.test'],
            $managerData
        );
        $managerId = DB::table('users')->where('email', 'manager@acme.test')->value('id');

        $agent = DB::table('users')->where('email', 'agent@acme.test')->first();

        $agentData = [
            'name' => 'Andy Agent',
            'password' => Hash::make('password'),
            'tenant_id' => $tenantId,
            'phone' => '555-000-0002',
            'address' => '2 Pet Street',
            'type' => 'employee',
            'status' => 'active',
            'created_at' => $agent->created_at ?? now(),
            'updated_at' => now(),
        ];

        if (! $agent) {
            $agentData['public_id'] = PublicIdGenerator::generate();
        }

        DB::table('users')->updateOrInsert(
            ['email' => 'agent@acme.test'],
            $agentData
        );
        $agentId = DB::table('users')->where('email', 'agent@acme.test')->value('id');

        // Clients
        $clients = [
            [
                'name' => 'Bella Barker',
                'email' => 'bella.barker@example.test',
                'phone' => '555-200-0001',
                'notes' => 'Owner of two golden retrievers.',
            ],
            [
                'name' => 'Charlie Cat',
                'phone' => '555-200-0002',
                'notes' => 'Prefers evening appointments and quiet rooms.',
            ],
            [
                'name' => 'Oscar Otter',
                'email' => 'oscar.otter@example.test',
                'notes' => 'New client referred by Bella Barker.',
            ],
        ];

        foreach ($clients as $client) {
            $existingClient = DB::table('clients')
                ->where('tenant_id', $tenantId)
                ->where('name', $client['name'])
                ->first();

            $clientData = [
                'email' => $client['email'] ?? null,
                'phone' => $client['phone'] ?? null,
                'notes' => $client['notes'] ?? null,
                'status' => 'active',
                'archived_at' => null,
                'deleted_at' => null,
                'created_at' => $existingClient->created_at ?? now(),
                'updated_at' => now(),
            ];

            if (! $existingClient) {
                $clientData['public_id'] = PublicIdGenerator::generate();
            }

            DB::table('clients')->updateOrInsert(
                ['tenant_id' => $tenantId, 'name' => $client['name']],
                $clientData
            );
        }

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
            $existingStatus = DB::table('task_statuses')
                ->where('tenant_id', $tenantId)
                ->where('slug', $status['slug'])
                ->first();

            $statusData = [
                'name' => $status['name'],
                'color' => $status['color'],
                'position' => $index + 1,
                'created_at' => $existingStatus->created_at ?? now(),
                'updated_at' => now(),
            ];

            if (! $existingStatus) {
                $statusData['public_id'] = PublicIdGenerator::generate();
            }

            DB::table('task_statuses')->updateOrInsert(
                ['tenant_id' => $tenantId, 'slug' => $status['slug']],
                $statusData
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

        $taskType = DB::table('task_types')
            ->where('tenant_id', $tenantId)
            ->where('name', 'General Task')
            ->first();

        $taskTypeData = [
            'schema_json' => json_encode($schema),
            'statuses' => json_encode($typeStatuses),
            'status_flow_json' => json_encode($transitions),
            'created_at' => $taskType->created_at ?? now(),
            'updated_at' => now(),
        ];

        if (! $taskType) {
            $taskTypeData['public_id'] = PublicIdGenerator::generate();
        }

        DB::table('task_types')->updateOrInsert(
            ['tenant_id' => $tenantId, 'name' => 'General Task'],
            $taskTypeData
        );
    }
}
