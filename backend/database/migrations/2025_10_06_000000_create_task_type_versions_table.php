<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_type_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_type_id');
            $table->string('semver');
            $table->json('schema_json')->nullable();
            $table->json('statuses')->nullable();
            $table->json('status_flow_json')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('deprecated_at')->nullable();
            $table->timestamps();

            $table->foreign('task_type_id')->references('id')->on('task_types')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('task_types', function (Blueprint $table) {
            if (! Schema::hasColumn('task_types', 'current_version_id')) {
                $table->unsignedBigInteger('current_version_id')->nullable()->after('require_subtasks_complete');
                $table->foreign('current_version_id')->references('id')->on('task_type_versions')->nullOnDelete();
            }
        });

        Schema::table('tasks', function (Blueprint $table) {
            if (! Schema::hasColumn('tasks', 'task_type_version_id')) {
                $table->unsignedBigInteger('task_type_version_id')->nullable()->after('task_type_id');
                $table->foreign('task_type_version_id')->references('id')->on('task_type_versions')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tasks', 'task_type_version_id')) {
                $table->dropForeign(['task_type_version_id']);
                $table->dropColumn('task_type_version_id');
            }
        });

        Schema::table('task_types', function (Blueprint $table) {
            if (Schema::hasColumn('task_types', 'current_version_id')) {
                $table->dropForeign(['current_version_id']);
                $table->dropColumn('current_version_id');
            }
        });

        Schema::dropIfExists('task_type_versions');
    }
};
