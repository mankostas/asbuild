<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('upload_chunks', function (Blueprint $table) {
            $table->id();
            $table->string('upload_id');
            $table->unsignedInteger('chunk_index');
            $table->string('path');
            $table->timestamps();
            $table->unique(['upload_id', 'chunk_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('upload_chunks');
    }
};
