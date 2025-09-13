<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        // Create demo tenants
        for ($i = 1; $i <= 3; $i++) {
            DB::table('tenants')->insert([
                'name' => "Demo Tenant {$i}",
                'quota_storage_mb' => 0,
                'features' => json_encode([]),
                'phone' => '123-456-7890',
                'address' => '123 Main St',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create demo teams
        for ($i = 1; $i <= 10; $i++) {
            $tenantId = (($i - 1) % 3) + 1;
            DB::table('teams')->insert([
                'tenant_id' => $tenantId,
                'name' => "Demo Team {$i}",
                'description' => "Demo team {$i}",
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create demo users
        for ($i = 1; $i <= 20; $i++) {
            $tenantId = (($i - 1) % 3) + 1;
            DB::table('users')->insert([
                'name' => "Demo User {$i}",
                'email' => "user{$i}@example.com",
                'password' => Hash::make('password'),
                'tenant_id' => $tenantId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Assign each user to a team
        for ($i = 1; $i <= 20; $i++) {
            $teamId = (($i - 1) % 10) + 1;
            DB::table('team_employee')->insert([
                'team_id' => $teamId,
                'employee_id' => $i,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('team_employee')->whereIn('team_id', function ($query) {
            $query->select('id')->from('teams')->where('name', 'like', 'Demo Team %');
        })->delete();

        DB::table('users')->where('email', 'like', 'user%@example.com')->delete();
        DB::table('teams')->where('name', 'like', 'Demo Team %')->delete();
        DB::table('tenants')->where('name', 'like', 'Demo Tenant %')->delete();
    }
};

