<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleUserSeeder extends Seeder
{
    public function run(): void
    {
        $roleId = DB::table('roles')
            ->where('tenant_id', 1)
            ->where('slug', 'super_admin')
            ->value('id');

        $userId = DB::table('users')
            ->where('email', 'anastasiou.ks@gmail.com')
            ->value('id');

        DB::table('role_user')->updateOrInsert(
            ['role_id' => $roleId, 'user_id' => $userId, 'tenant_id' => 1],
            ['created_at' => now(), 'updated_at' => now()]
        );
    }
}
