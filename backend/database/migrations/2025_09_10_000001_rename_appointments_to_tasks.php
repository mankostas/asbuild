<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('appointments') && ! Schema::hasTable('tasks')) {
            Schema::rename('appointments', 'tasks');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tasks') && ! Schema::hasTable('appointments')) {
            Schema::rename('tasks', 'appointments');
        }
    }
};
