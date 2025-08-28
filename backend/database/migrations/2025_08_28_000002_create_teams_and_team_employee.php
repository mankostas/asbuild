<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('teams')) {
            Schema::create('teams', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->text('description')->nullable();
                $table->timestamps();
                $table->unique(['tenant_id', 'name']);
            });
        }

        if (! Schema::hasTable('team_employee')) {
            Schema::create('team_employee', function (Blueprint $table) {
                $table->id();
                $table->foreignId('team_id')->constrained()->cascadeOnDelete();
                $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['team_id', 'employee_id']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('team_employee')) {
            Schema::dropIfExists('team_employee');
        }

        if (Schema::hasTable('teams')) {
            Schema::dropIfExists('teams');
        }
    }
};

