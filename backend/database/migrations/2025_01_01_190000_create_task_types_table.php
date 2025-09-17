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
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->json('schema_json')->nullable();
            $table->json('fields_summary')->nullable();
            $table->json('statuses')->nullable();
            $table->json('status_flow_json')->nullable();
            $table->boolean('require_subtasks_complete')->default(false);
            $table->json('abilities_json')->nullable();
            $table->timestamps();
            $table->index('tenant_id');
            $table->unique(['tenant_id', 'name']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('task_type_id')
                ->nullable()
                ->after('tenant_id')
                ->constrained('task_types')
                ->nullOnDelete();
            $table->json('form_data')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('form_data');
            $table->dropConstrainedForeignId('task_type_id');
        });

        Schema::dropIfExists('task_types');
    }
};
