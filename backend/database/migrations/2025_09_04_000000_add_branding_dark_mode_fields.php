<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brandings', function (Blueprint $table) {
            if (!Schema::hasColumn('brandings', 'secondary_color')) {
                $table->string('secondary_color')->nullable()->after('color');
            }

            if (!Schema::hasColumn('brandings', 'color_dark')) {
                $after = Schema::hasColumn('brandings', 'secondary_color') ? 'secondary_color' : 'color';
                $table->string('color_dark')->nullable()->after($after);
            }

            if (!Schema::hasColumn('brandings', 'secondary_color_dark')) {
                $table->string('secondary_color_dark')->nullable()->after('color_dark');
            }

            if (!Schema::hasColumn('brandings', 'logo_dark')) {
                $table->string('logo_dark')->nullable()->after('logo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('brandings', function (Blueprint $table) {
            $columns = [];

            foreach (['secondary_color', 'color_dark', 'secondary_color_dark', 'logo_dark'] as $column) {
                if (Schema::hasColumn('brandings', $column)) {
                    $columns[] = $column;
                }
            }

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
