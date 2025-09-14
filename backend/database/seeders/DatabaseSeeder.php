<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            TenantSeeder::class,
            TenantBootstrapSeeder::class,
            RoleSeeder::class,
            SuperAdminSeeder::class,
            RoleUserSeeder::class,
            TenantBootstrapSeeder::class,
            BrandingSeeder::class,
        ]);
    }
}
