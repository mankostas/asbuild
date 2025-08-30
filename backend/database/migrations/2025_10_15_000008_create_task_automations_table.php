<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_automations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_type_id');
            $table->string('event');
            $table->json('conditions_json')->nullable();
            $table->json('actions_json');
            $table->boolean('enabled')->default(true);
            $table->timestamps();
            $table->foreign('task_type_id')->references('id')->on('task_types')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_automations');
    }
};
