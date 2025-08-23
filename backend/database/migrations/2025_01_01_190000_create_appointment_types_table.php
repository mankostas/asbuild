<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('form_schema')->nullable();
            $table->json('fields_summary')->nullable();
            $table->timestamps();
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->unsignedBigInteger('appointment_type_id')->nullable()->after('tenant_id');
            $table->json('form_data')->nullable()->after('kau_notes');
            $table->foreign('appointment_type_id')
                ->references('id')
                ->on('appointment_types')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['appointment_type_id']);
            $table->dropColumn('form_data');
            $table->dropColumn('appointment_type_id');
        });

        Schema::dropIfExists('appointment_types');
    }
};
