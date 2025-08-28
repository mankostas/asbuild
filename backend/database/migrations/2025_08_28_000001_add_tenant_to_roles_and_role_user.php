<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('roles') && ! Schema::hasColumn('roles', 'tenant_id')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete()->index();
            });
        }

        if (! Schema::hasTable('role_user')) {
            Schema::create('role_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('role_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['tenant_id', 'user_id', 'role_id']);
            });
        } else {
            if (! Schema::hasColumn('role_user', 'tenant_id')) {
                Schema::table('role_user', function (Blueprint $table) {
                    $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('role_user')) {
            if (Schema::hasColumn('role_user', 'tenant_id')) {
                Schema::table('role_user', function (Blueprint $table) {
                    $table->dropConstrainedForeignId('tenant_id');
                });
            } else {
                Schema::dropIfExists('role_user');
            }
        }

        if (Schema::hasTable('roles') && Schema::hasColumn('roles', 'tenant_id')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropConstrainedForeignId('tenant_id');
            });
        }
    }
};

