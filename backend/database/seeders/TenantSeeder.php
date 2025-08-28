<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('tenants')->updateOrInsert(
            ['id' => 1],
            [
                'name' => 'Super Admin Tenant',
                'quota_storage_mb' => 0,
                'features' => json_encode([]),
                'phone' => '123-456-7890',
                'address' => '123 Main St',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
