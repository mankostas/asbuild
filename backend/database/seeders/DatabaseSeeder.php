<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            TenantSeeder::class,
            RoleSeeder::class,
            SuperAdminSeeder::class,
            RoleUserSeeder::class,
            BrandingSeeder::class,
        ]);

        if (env('ENABLE_DEMO_SEEDER', false)) {
            $this->call(TenantBootstrapSeeder::class);
        }
    }
}
