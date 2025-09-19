<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (! Schema::hasColumn('tenants', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('address');
            }

            if (! Schema::hasColumn('tenants', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'archived_at')) {
                $table->dropColumn('archived_at');
            }

            if (Schema::hasColumn('tenants', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
