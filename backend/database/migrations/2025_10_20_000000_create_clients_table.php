<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'name']);
            $table->index('user_id');
            $table->index('archived_at');
        });

        Schema::table('task_types', function (Blueprint $table) {
            $table->foreignId('client_id')
                ->nullable()
                ->after('tenant_id')
                ->constrained('clients')
                ->nullOnDelete();
            $table->index('client_id');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('client_id')
                ->nullable()
                ->after('task_type_id')
                ->constrained('clients')
                ->nullOnDelete();
            $table->index('client_id');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('client_id');
        });

        Schema::table('task_types', function (Blueprint $table) {
            $table->dropConstrainedForeignId('client_id');
        });

        Schema::dropIfExists('clients');
    }
};
