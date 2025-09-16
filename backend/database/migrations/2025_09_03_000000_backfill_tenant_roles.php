<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Tenant;
use Database\Seeders\DefaultFeatureRolesSeeder;

return new class extends Migration
{
    public function up(): void
    {
        $defaultFeatures = ['dashboard','tasks','notifications','roles','task_types','teams','task_statuses','themes'];

        Tenant::query()->lazy()->each(function (Tenant $tenant) use ($defaultFeatures) {
            if (empty($tenant->features)) {
                $tenant->features = $defaultFeatures;
                $tenant->save();
            }

            DefaultFeatureRolesSeeder::syncDefaultRolesForFeatures($tenant, $tenant->selectedFeatureAbilities());
        });
    }

    public function down(): void
    {
        // no-op
    }
};
