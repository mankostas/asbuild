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
                if (! Schema::hasColumn('tasks', 'title')) {
                    $table->string('title')->nullable();
                }
                if (! Schema::hasColumn('tasks', 'description')) {
                    $table->text('description')->nullable();
                }
                if (! Schema::hasColumn('tasks', 'priority')) {
                    $table->string('priority')->nullable();
                }
                if (! Schema::hasColumn('tasks', 'due_at')) {
                    $table->dateTime('due_at')->nullable();
                }
                if (! Schema::hasColumn('tasks', 'estimate_minutes')) {
                    $table->integer('estimate_minutes')->nullable();
                }
                if (! Schema::hasColumn('tasks', 'reporter_user_id')) {
                    $table->unsignedBigInteger('reporter_user_id')->nullable();
                }
                if (! Schema::hasColumn('tasks', 'status_slug')) {
                    $table->string('status_slug')->nullable();
                }
                if (! Schema::hasColumn('tasks', 'board_position')) {
                    $table->integer('board_position')->nullable();
                }
                if (! Schema::hasColumn('tasks', 'assigned_user_id')) {
                    $table->unsignedBigInteger('assigned_user_id')->nullable();
                    $table->index('assigned_user_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tasks')) {
            Schema::table('tasks', function (Blueprint $table) {
                foreach ([
                    'title',
                    'description',
                    'priority',
                    'due_at',
                    'estimate_minutes',
                    'reporter_user_id',
                    'status_slug',
                    'board_position',
                    'assigned_user_id',
                ] as $column) {
                    if (Schema::hasColumn('tasks', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
