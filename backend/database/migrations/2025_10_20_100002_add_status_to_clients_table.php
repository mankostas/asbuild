<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('status')->default('active')->after('notes');
            $table->index('status');
        });

        DB::table('clients')->whereNull('status')->update(['status' => 'active']);
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex('clients_status_index');
            $table->dropColumn('status');
        });
    }
};
