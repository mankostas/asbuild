<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            TenantSeeder::class,
            SuperAdminSeeder::class,
            RoleSeeder::class,
            RoleUserSeeder::class,
            TenantSettingsSeeder::class,
        ]);
    }
}
