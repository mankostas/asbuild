<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manuals', function (Blueprint $table) {
            $table->foreignId('client_id')
                ->nullable()
                ->after('tenant_id')
                ->constrained()
                ->nullOnDelete();
            $table->index('client_id');
        });
    }

    public function down(): void
    {
        Schema::table('manuals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('client_id');
        });
    }
};
