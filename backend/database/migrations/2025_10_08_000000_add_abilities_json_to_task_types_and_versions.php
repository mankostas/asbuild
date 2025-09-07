<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('task_types', function (Blueprint $table) {
            if (! Schema::hasColumn('task_types', 'abilities_json')) {
                $table->json('abilities_json')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('task_types', function (Blueprint $table) {
            if (Schema::hasColumn('task_types', 'abilities_json')) {
                $table->dropColumn('abilities_json');
            }
        });
    }
};
