<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_sla_policies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_type_id');
            $table->string('priority');
            $table->integer('response_within_mins')->nullable();
            $table->integer('resolve_within_mins')->nullable();
            $table->json('calendar_json')->nullable();
            $table->timestamps();
            $table->unique(['task_type_id', 'priority']);
            $table->foreign('task_type_id')->references('id')->on('task_types')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_sla_policies');
    }
};
