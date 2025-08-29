<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('appointment_types') && ! Schema::hasTable('task_types')) {
            Schema::rename('appointment_types', 'task_types');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('task_types') && ! Schema::hasTable('appointment_types')) {
            Schema::rename('task_types', 'appointment_types');
        }
    }
};
