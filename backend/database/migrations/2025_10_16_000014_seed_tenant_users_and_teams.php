<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Database\Seeders\TenantBootstrapSeeder;

return new class extends Migration
{
    public function up(): void
    {
        (new TenantBootstrapSeeder())->run();
    }

    public function down(): void
    {
        // Remove seeded team employees
        $tenantId = DB::table('tenants')->where('id', 2)->value('id');
        if ($tenantId) {
            $teamId = DB::table('teams')
                ->where('tenant_id', $tenantId)
                ->where('name', 'Front Desk')
                ->value('id');
            if ($teamId) {
                DB::table('team_employee')->where('team_id', $teamId)->delete();
                DB::table('teams')->where('id', $teamId)->delete();
            }

            // Remove seeded users and role assignments
            $userIds = DB::table('users')
                ->whereIn('email', ['manager@acme.test', 'agent@acme.test'])
                ->pluck('id');
            if ($userIds->isNotEmpty()) {
                DB::table('role_user')
                    ->whereIn('user_id', $userIds)
                    ->where('tenant_id', $tenantId)
                    ->delete();
                DB::table('users')->whereIn('id', $userIds)->delete();
            }

            // Remove roles created for this tenant
            DB::table('roles')->where('tenant_id', $tenantId)->delete();

            // Remove seeded task types and statuses
            DB::table('task_types')->where('tenant_id', $tenantId)->delete();
            DB::table('task_statuses')->where('tenant_id', $tenantId)->delete();

            // Remove tenant
            DB::table('tenants')->where('id', $tenantId)->delete();
        }

        // Remove global super admin role inserted by seeder if unused
        $superAdminRoleId = DB::table('roles')
            ->whereNull('tenant_id')
            ->where('slug', 'super_admin')
            ->value('id');
        if ($superAdminRoleId && !DB::table('role_user')->where('role_id', $superAdminRoleId)->exists()) {
            DB::table('roles')->where('id', $superAdminRoleId)->delete();
        }
    }
};
