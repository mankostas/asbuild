<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_comment_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_comment_id');
            $table->unsignedBigInteger('file_id');
            $table->timestamps();
            $table->foreign('task_comment_id')
                ->references('id')->on('task_comments')
                ->onDelete('cascade');
            $table->foreign('file_id')
                ->references('id')->on('files')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_comment_files');
    }
};
