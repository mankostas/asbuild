<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('task_types') && ! Schema::hasColumn('task_types', 'tenant_id')) {
            Schema::table('task_types', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                $table->index('tenant_id', 'task_types_tenant_id_index');
                $table->unique(['tenant_id', 'name'], 'task_types_tenant_id_name_unique');
            });
        }

        if (Schema::hasTable('statuses') && ! Schema::hasColumn('statuses', 'tenant_id')) {
            Schema::table('statuses', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                $table->index('tenant_id', 'statuses_tenant_id_index');
                $table->unique(['tenant_id', 'name'], 'statuses_tenant_id_name_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('task_types') && Schema::hasColumn('task_types', 'tenant_id')) {
            Schema::table('task_types', function (Blueprint $table) {
                $table->dropUnique('task_types_tenant_id_name_unique');
                $table->dropIndex('task_types_tenant_id_index');
                $table->dropColumn('tenant_id');
            });
        }

        if (Schema::hasTable('statuses') && Schema::hasColumn('statuses', 'tenant_id')) {
            Schema::table('statuses', function (Blueprint $table) {
                $table->dropUnique('statuses_tenant_id_name_unique');
                $table->dropIndex('statuses_tenant_id_index');
                $table->dropColumn('tenant_id');
            });
        }
    }
};
