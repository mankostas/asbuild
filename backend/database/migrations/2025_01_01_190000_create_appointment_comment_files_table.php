<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_comment_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('appointment_comment_id');
            $table->unsignedBigInteger('file_id');
            $table->timestamps();
            $table->foreign('appointment_comment_id')
                ->references('id')->on('appointment_comments')
                ->onDelete('cascade');
            $table->foreign('file_id')
                ->references('id')->on('files')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_comment_files');
    }
};
