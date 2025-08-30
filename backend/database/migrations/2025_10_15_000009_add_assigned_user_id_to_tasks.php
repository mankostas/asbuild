<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tasks')) {
            Schema::table('tasks', function (Blueprint $table) {
                if (Schema::hasColumn('tasks', 'assignee_type')) {
                    $table->dropColumn('assignee_type');
                }
                if (Schema::hasColumn('tasks', 'assignee_id')) {
                    $table->dropColumn('assignee_id');
                }
                if (! Schema::hasColumn('tasks', 'assigned_user_id')) {
                    $table->unsignedBigInteger('assigned_user_id')->nullable()->after('user_id');
                    $table->index('assigned_user_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tasks')) {
            Schema::table('tasks', function (Blueprint $table) {
                if (Schema::hasColumn('tasks', 'assigned_user_id')) {
                    $table->dropColumn('assigned_user_id');
                }
                if (! Schema::hasColumn('tasks', 'assignee_type')) {
                    $table->string('assignee_type', 50)->nullable();
                }
                if (! Schema::hasColumn('tasks', 'assignee_id')) {
                    $table->unsignedBigInteger('assignee_id')->nullable();
                    $table->index(['assignee_type', 'assignee_id']);
                }
            });
        }
    }
};

