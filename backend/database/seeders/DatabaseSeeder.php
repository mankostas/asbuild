<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            InitialSystemSeeder::class,
        ]);

        if (env('ENABLE_DEMO_SEEDER', false)) {
            $this->call(TenantBootstrapSeeder::class);
        }
    }
}
