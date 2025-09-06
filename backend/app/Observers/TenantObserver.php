<?php

namespace App\Observers;

use App\Models\Tenant;
use Database\Seeders\DefaultFeatureRolesSeeder;

class TenantObserver
{
    public function created(Tenant $tenant): void
    {
        DefaultFeatureRolesSeeder::syncDefaultRolesForFeatures($tenant, $tenant->feature_abilities ?? []);
    }

    public function updated(Tenant $tenant): void
    {
        if ($tenant->wasChanged(['features', 'feature_abilities'])) {
            DefaultFeatureRolesSeeder::syncDefaultRolesForFeatures($tenant, $tenant->feature_abilities ?? []);
        }
    }
}
