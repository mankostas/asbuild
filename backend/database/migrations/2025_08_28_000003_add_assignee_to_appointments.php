<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('appointments')) {
            Schema::table('appointments', function (Blueprint $table) {
                if (! Schema::hasColumn('appointments', 'assignee_type')) {
                    $table->string('assignee_type', 50)->nullable();
                }
                if (! Schema::hasColumn('appointments', 'assignee_id')) {
                    $table->unsignedBigInteger('assignee_id')->nullable();
                }
            });

            if (! Schema::hasColumn('appointments', 'assignee_type') || ! Schema::hasColumn('appointments', 'assignee_id')) {
                // ensure index if columns were just added
                Schema::table('appointments', function (Blueprint $table) {
                    $table->index(['assignee_type', 'assignee_id']);
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('appointments')) {
            Schema::table('appointments', function (Blueprint $table) {
                if (Schema::hasColumn('appointments', 'assignee_type')) {
                    $table->dropColumn('assignee_type');
                }
                if (Schema::hasColumn('appointments', 'assignee_id')) {
                    $table->dropColumn('assignee_id');
                }
            });
        }
    }
};

