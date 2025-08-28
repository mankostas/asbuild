<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('roles')->insert([
            'id' => 1,
            'tenant_id' => 1,
            'name' => 'SuperAdmin',
            // SuperAdmin is the root role; use level 0 so other roles can build from it
            'level' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
