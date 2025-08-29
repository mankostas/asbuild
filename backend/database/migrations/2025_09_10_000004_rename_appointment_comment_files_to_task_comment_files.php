<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('appointment_comment_files') && ! Schema::hasTable('task_comment_files')) {
            Schema::rename('appointment_comment_files', 'task_comment_files');
            Schema::table('task_comment_files', function (Blueprint $table) {
                if (Schema::hasColumn('task_comment_files', 'appointment_comment_id')) {
                    $table->renameColumn('appointment_comment_id', 'task_comment_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('task_comment_files') && ! Schema::hasTable('appointment_comment_files')) {
            Schema::table('task_comment_files', function (Blueprint $table) {
                if (Schema::hasColumn('task_comment_files', 'task_comment_id')) {
                    $table->renameColumn('task_comment_id', 'appointment_comment_id');
                }
            });
            Schema::rename('task_comment_files', 'appointment_comment_files');
        }
    }
};
