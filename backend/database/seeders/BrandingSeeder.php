<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BrandingSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('brandings')->updateOrInsert(
            ['tenant_id' => null],
            [
                'name' => 'Default Brand',
                'color' => '#4669fa',
                'color_dark' => '#4669fa',
                'secondary_color' => '#A0AEC0',
                'secondary_color_dark' => '#A0AEC0',
                'logo' => null,
                'logo_dark' => null,
                'email_from' => null,
                'footer_left' => 'Default left footer',
                'footer_right' => 'Default right footer',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
