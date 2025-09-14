<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->updateOrInsert(
            ['email' => 'anastasiou.ks@gmail.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('Swordfish01!@#'),
                'tenant_id' => 1,
                'phone' => '123-456-7890',
                'address' => '456 Admin St',
                'type' => 'super_admin',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
