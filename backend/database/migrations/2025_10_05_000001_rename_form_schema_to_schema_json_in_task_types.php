<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('task_types', 'form_schema')) {
            Schema::table('task_types', function ($table) {
                $table->renameColumn('form_schema', 'schema_json');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('task_types', 'schema_json')) {
            Schema::table('task_types', function ($table) {
                $table->renameColumn('schema_json', 'form_schema');
            });
        }
    }
};
