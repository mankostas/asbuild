<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantRolesBackfillSeeder extends Seeder
{
    public function run(): void
    {
        $defaultFeatures = [
            'tasks',
            'notifications',
            'roles',
            'task_types',
            'task_statuses',
            'teams',
            'themes',
            'billing',
        ];
        $defaultFeatures = array_values(array_intersect($defaultFeatures, config('features', [])));

        Tenant::query()->lazy()->each(function (Tenant $tenant) use ($defaultFeatures) {
            if (empty($tenant->features)) {
                $tenant->features = $defaultFeatures;
                $tenant->save();
            }

            DefaultFeatureRolesSeeder::syncDefaultRolesForFeatures($tenant, $tenant->selectedFeatureAbilities());
        });
    }
}
