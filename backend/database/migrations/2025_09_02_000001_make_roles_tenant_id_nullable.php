<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            return; // fresh sqlite databases use the updated schema
        }

        if (Schema::hasTable('roles') && Schema::hasColumn('roles', 'tenant_id')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropForeign(['tenant_id']);
            });

            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE roles MODIFY tenant_id BIGINT UNSIGNED NULL');
            } else {
                DB::statement('ALTER TABLE roles ALTER COLUMN tenant_id DROP NOT NULL');
            }

            Schema::table('roles', function (Blueprint $table) {
                $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            return;
        }

        if (Schema::hasTable('roles') && Schema::hasColumn('roles', 'tenant_id')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropForeign(['tenant_id']);
            });

            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE roles MODIFY tenant_id BIGINT UNSIGNED NOT NULL');
            } else {
                DB::statement('ALTER TABLE roles ALTER COLUMN tenant_id SET NOT NULL');
            }

            Schema::table('roles', function (Blueprint $table) {
                $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            });
        }
    }
};
