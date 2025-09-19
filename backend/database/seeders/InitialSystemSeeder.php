<?php

namespace Database\Seeders;

use App\Models\Branding;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class InitialSystemSeeder extends Seeder
{
    public function run(): void
    {
        $features = config('features', []);

        $tenant = Tenant::query()->updateOrCreate(
            ['id' => 1],
            [
                'name' => 'Super Admin Tenant',
                'quota_storage_mb' => 0,
                'features' => $features,
                'phone' => '123-456-7890',
                'address' => '123 Main St',
                'archived_at' => null,
                'deleted_at' => null,
            ]
        );

        $selectedAbilities = $tenant->selectedFeatureAbilities();
        $tenant->forceFill(['feature_abilities' => $selectedAbilities])->save();

        DefaultFeatureRolesSeeder::syncDefaultRolesForFeatures($tenant, $selectedAbilities);

        $superAdminRole = Role::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'slug' => 'super_admin'],
            [
                'name' => 'SuperAdmin',
                'description' => 'Full system administrator',
                'abilities' => ['*'],
                'level' => 0,
            ]
        );

        $superAdmin = User::query()->updateOrCreate(
            ['email' => 'anastasiou.ks@gmail.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('Swordfish01!@#'),
                'tenant_id' => $tenant->id,
                'phone' => '123-456-7890',
                'address' => '456 Admin St',
                'type' => 'super_admin',
                'status' => 'active',
            ]
        );

        $superAdmin->roles()->syncWithoutDetaching([
            $superAdminRole->id => ['tenant_id' => $tenant->id],
        ]);

        Branding::query()->updateOrCreate(
            ['tenant_id' => null],
            [
                'name' => 'Default Brand',
                'color' => '#4669fa',
                'secondary_color' => '#A0AEC0',
                'color_dark' => '#4669fa',
                'secondary_color_dark' => '#A0AEC0',
                'logo' => null,
                'logo_dark' => null,
                'email_from' => null,
                'footer_left' => 'Default left footer',
                'footer_right' => 'Default right footer',
            ]
        );
    }
}
