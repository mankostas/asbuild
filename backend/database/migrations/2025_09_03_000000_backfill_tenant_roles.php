<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Tenant;
use Database\Seeders\DefaultFeatureRolesSeeder;

return new class extends Migration
{
    public function up(): void
    {
        $defaultFeatures = ['appointments','roles','types','teams','statuses','themes'];

        Tenant::query()->lazy()->each(function (Tenant $tenant) use ($defaultFeatures) {
            if (empty($tenant->features)) {
                $tenant->features = $defaultFeatures;
                $tenant->save();
            }

            DefaultFeatureRolesSeeder::syncDefaultRolesForFeatures($tenant);
        });
    }

    public function down(): void
    {
        // no-op
    }
};
