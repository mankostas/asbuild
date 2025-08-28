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
            'tenant_id' => null,
            'name' => 'SuperAdmin',
            'slug' => 'super_admin',
            // SuperAdmin is the root role; use level 0 so other roles can build from it
            'level' => 0,
            'abilities' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
