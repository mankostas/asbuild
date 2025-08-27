<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TenantSettingsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('tenant_settings')->insert([
            [
                'tenant_id' => 1,
                'key' => 'branding',
                'value' => json_encode(['name' => 'Default Brand']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tenant_id' => 1,
                'key' => 'footer',
                'value' => 'Default footer',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
