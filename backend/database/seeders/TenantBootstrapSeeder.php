<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Appointment;

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
                'features' => json_encode([]),
                'phone' => '555-123-4567',
                'address' => '1 Pet Street',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        $tenantId = DB::table('tenants')->where('id', 1)->value('id');

        // Tenant roles
        $managerAbilities = ['appointments.manage', 'teams.manage', 'statuses.manage'];
        $agentAbilities = ['appointments.view', 'appointments.update'];

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

        // Default statuses
        $defaultStatuses = [
            Appointment::STATUS_DRAFT,
            Appointment::STATUS_ASSIGNED,
            Appointment::STATUS_IN_PROGRESS,
            Appointment::STATUS_COMPLETED,
        ];

        foreach ($defaultStatuses as $status) {
            DB::table('statuses')->updateOrInsert(
                ['tenant_id' => $tenantId, 'name' => $status],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        // Appointment type with assignee
        $schema = [
            'type' => 'object',
            'properties' => [
                'assignee' => [
                    'type' => 'object',
                    'kind' => 'assignee',
                    'properties' => [
                        'kind' => ['type' => 'string', 'enum' => ['team', 'employee']],
                        'id' => ['type' => 'integer'],
                    ],
                    'required' => ['kind', 'id'],
                ],
            ],
            'required' => ['assignee'],
        ];

        $fieldsSummary = ['assignee' => 'Assignee'];

        $transitions = [
            Appointment::STATUS_DRAFT => [Appointment::STATUS_ASSIGNED],
            Appointment::STATUS_ASSIGNED => [Appointment::STATUS_IN_PROGRESS],
            Appointment::STATUS_IN_PROGRESS => [Appointment::STATUS_COMPLETED],
            Appointment::STATUS_COMPLETED => [],
        ];

        DB::table('appointment_types')->updateOrInsert(
            ['tenant_id' => $tenantId, 'name' => 'Basic'],
            [
                'form_schema' => json_encode($schema),
                'fields_summary' => json_encode($fieldsSummary),
                'statuses' => json_encode($transitions),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
