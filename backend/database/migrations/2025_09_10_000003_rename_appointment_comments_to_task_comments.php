<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('appointment_comments') && ! Schema::hasTable('task_comments')) {
            Schema::rename('appointment_comments', 'task_comments');
            Schema::table('task_comments', function (Blueprint $table) {
                if (Schema::hasColumn('task_comments', 'appointment_id')) {
                    $table->renameColumn('appointment_id', 'task_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('task_comments') && ! Schema::hasTable('appointment_comments')) {
            Schema::table('task_comments', function (Blueprint $table) {
                if (Schema::hasColumn('task_comments', 'task_id')) {
                    $table->renameColumn('task_id', 'appointment_id');
                }
            });
            Schema::rename('task_comments', 'appointment_comments');
        }
    }
};
