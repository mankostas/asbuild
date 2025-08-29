<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brandings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('color')->nullable();
            $table->string('secondary_color')->nullable();
            $table->string('color_dark')->nullable();
            $table->string('secondary_color_dark')->nullable();
            $table->string('logo')->nullable();
            $table->string('logo_dark')->nullable();
            $table->string('email_from')->nullable();
            $table->string('footer_left')->nullable();
            $table->string('footer_right')->nullable();
            $table->timestamps();
            $table->unique('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brandings');
    }
};
