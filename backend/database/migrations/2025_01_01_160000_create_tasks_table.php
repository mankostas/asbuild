<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('draft');
            $table->string('status_slug')->nullable();
            $table->string('previous_status_slug')->nullable();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('priority')->nullable();
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('due_at')->nullable();
            $table->dateTime('sla_start_at')->nullable();
            $table->dateTime('sla_end_at')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->integer('estimate_minutes')->nullable();
            $table->foreignId('reporter_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('board_position')->nullable();
            $table->text('kau_notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index('status_slug');
            $table->index('assigned_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
