<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('form_schema')->nullable();
            $table->json('fields_summary')->nullable();
            $table->timestamps();
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedBigInteger('task_type_id')->nullable()->after('tenant_id');
            $table->json('form_data')->nullable()->after('kau_notes');
            $table->foreign('task_type_id')
                ->references('id')
                ->on('task_types')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['task_type_id']);
            $table->dropColumn('form_data');
            $table->dropColumn('task_type_id');
        });

        Schema::dropIfExists('task_types');
    }
};
