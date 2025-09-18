<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (Schema::hasColumn('clients', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropIndex('clients_user_id_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (Schema::hasColumn('clients', 'user_id')) {
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
                $table->index('user_id');
            }
        });
    }
};
