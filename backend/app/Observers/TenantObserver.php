<?php

namespace App\Observers;

use App\Models\Tenant;
use Database\Seeders\DefaultFeatureRolesSeeder;

class TenantObserver
{
    public function created(Tenant $tenant): void
    {
        DefaultFeatureRolesSeeder::syncDefaultRolesForFeatures($tenant);
    }

    public function updated(Tenant $tenant): void
    {
        if ($tenant->wasChanged('features')) {
            DefaultFeatureRolesSeeder::syncDefaultRolesForFeatures($tenant);
        }
    }
}
