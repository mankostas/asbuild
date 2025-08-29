<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('roles')->updateOrInsert(
            ['tenant_id' => null, 'slug' => 'super_admin'],
            [
                'name' => 'Super Admin',
                'level' => 0,
                'abilities' => json_encode(['*']),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('roles')
            ->whereNull('tenant_id')
            ->where('slug', 'super_admin')
            ->delete();
    }
};

