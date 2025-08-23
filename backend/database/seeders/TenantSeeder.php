<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('tenants')->insert([
            'id' => 1,
            'name' => 'Default Tenant',
            'quota_storage_mb' => 0,
            'features' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
