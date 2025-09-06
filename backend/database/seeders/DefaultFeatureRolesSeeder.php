<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DefaultFeatureRolesSeeder extends Seeder
{
    public static function syncDefaultRolesForFeatures(Tenant $tenant, array $abilityMap = []): void
    {
        $features = $tenant->features ?? [];
        $map = config('feature_map', []);

        $roles = [];

        foreach ($features as $feature) {
            $config = $map[$feature] ?? null;
            if ($config === null) {
                continue;
            }

            $selected = $abilityMap[$feature] ?? [];
            $label = $config['label'] ?? ucfirst($feature);
            $abilities = $config['abilities'] ?? [];

            $viewerAbilities = array_filter(
                $abilities,
                fn ($ability) => str_ends_with($ability, '.view')
            );
            $roles[] = [
                'slug' => "{$feature}_viewer",
                'name' => "$label Viewer",
                'abilities' => array_intersect($viewerAbilities, $selected),
                'level' => 3,
            ];

            $editorAbilities = array_filter(
                $abilities,
                fn ($ability) => str_ends_with($ability, '.view')
                    || str_ends_with($ability, '.create')
                    || str_ends_with($ability, '.update')
            );
            $roles[] = [
                'slug' => "{$feature}_editor",
                'name' => "$label Editor",
                'abilities' => array_intersect($editorAbilities, $selected),
                'level' => 3,
            ];

            $roles[] = [
                'slug' => "{$feature}_manager",
                'name' => "$label Manager",
                'abilities' => array_intersect($abilities, $selected),
                'level' => 2,
            ];
        }

        foreach ($roles as $role) {
            $existing = DB::table('roles')
                ->where('tenant_id', $tenant->id)
                ->where('slug', $role['slug'])
                ->first();

            $abilities = $role['abilities'];
            $level = $role['level'];

            if ($existing) {
                $existingAbilities = json_decode($existing->abilities, true) ?? [];
                $abilities = array_values(array_unique(array_merge($existingAbilities, $abilities)));
                $level = min($existing->level, $level);
            }

            DB::table('roles')->updateOrInsert(
                ['tenant_id' => $tenant->id, 'slug' => $role['slug']],
                [
                    'name' => $role['name'],
                    'abilities' => json_encode($abilities),
                    'level' => $level,
                    'created_at' => $existing->created_at ?? now(),
                    'updated_at' => now(),
                ]
            );
        }

        // Update tenant role abilities
        $tenantRole = DB::table('roles')
            ->where('tenant_id', $tenant->id)
            ->where('slug', 'tenant')
            ->first();

        if ($tenantRole) {
            $abilities = array_values(array_unique(array_merge(
                json_decode($tenantRole->abilities, true) ?? [],
                $tenant->allowedAbilities()
            )));

            if (in_array('reports', $features, true)) {
                $abilities = array_values(array_unique(array_merge($abilities, [
                    'reports.view',
                    'reports.manage',
                ])));
            }

            DB::table('roles')
                ->where('id', $tenantRole->id)
                ->update([
                    'abilities' => json_encode($abilities),
                    'updated_at' => now(),
                ]);
        }
    }
}
