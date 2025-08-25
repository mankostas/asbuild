<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->insert([
            'id' => 1,
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'tenant_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
