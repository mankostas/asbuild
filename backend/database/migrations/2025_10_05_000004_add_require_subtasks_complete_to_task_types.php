<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_types', function (Blueprint $table) {
            $table->boolean('require_subtasks_complete')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('task_types', function (Blueprint $table) {
            $table->dropColumn('require_subtasks_complete');
        });
    }
};
