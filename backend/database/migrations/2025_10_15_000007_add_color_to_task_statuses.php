<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_statuses', function (Blueprint $table) {
            if (! Schema::hasColumn('task_statuses', 'color')) {
                $table->string('color', 7)->nullable()->after('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('task_statuses', function (Blueprint $table) {
            if (Schema::hasColumn('task_statuses', 'color')) {
                $table->dropColumn('color');
            }
        });
    }
};
