<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantRolesBackfillSeeder extends Seeder
{
    public function run(): void
    {
        $defaultFeatures = ['tasks','notifications','roles','types','teams','statuses','themes'];

        Tenant::query()->lazy()->each(function (Tenant $tenant) use ($defaultFeatures) {
            if (empty($tenant->features)) {
                $tenant->features = $defaultFeatures;
                $tenant->save();
            }

            DefaultFeatureRolesSeeder::syncDefaultRolesForFeatures($tenant);
        });
    }
}
