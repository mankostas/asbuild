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
                if (! Schema::hasColumn('tasks', 'assignee_type')) {
                    $table->string('assignee_type', 50)->nullable();
                }
                if (! Schema::hasColumn('tasks', 'assignee_id')) {
                    $table->unsignedBigInteger('assignee_id')->nullable();
                }
            });

            if (! Schema::hasColumn('tasks', 'assignee_type') || ! Schema::hasColumn('tasks', 'assignee_id')) {
                // ensure index if columns were just added
                Schema::table('tasks', function (Blueprint $table) {
                    $table->index(['assignee_type', 'assignee_id']);
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tasks')) {
            Schema::table('tasks', function (Blueprint $table) {
                if (Schema::hasColumn('tasks', 'assignee_type')) {
                    $table->dropColumn('assignee_type');
                }
                if (Schema::hasColumn('tasks', 'assignee_id')) {
                    $table->dropColumn('assignee_id');
                }
            });
        }
    }
};

