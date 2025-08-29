<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brandings', function (Blueprint $table) {
            $table->string('secondary_color')->nullable()->after('color');
            $table->string('color_dark')->nullable()->after('secondary_color');
            $table->string('secondary_color_dark')->nullable()->after('color_dark');
            $table->string('logo_dark')->nullable()->after('logo');
        });
    }

    public function down(): void
    {
        Schema::table('brandings', function (Blueprint $table) {
            $table->dropColumn([
                'secondary_color',
                'color_dark',
                'secondary_color_dark',
                'logo_dark',
            ]);
        });
    }
};
