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
        $clientViewerAbilities = [];
        $clientContributorAbilities = [];

        foreach ($features as $feature) {
            $config = $map[$feature] ?? null;
            if ($config === null) {
                continue;
            }

            $selected = $abilityMap[$feature] ?? [];
            $label = $config['label'] ?? ucfirst($feature);
            $abilities = $config['abilities'] ?? [];

            $selectedAbilities = array_values(array_intersect($abilities, $selected));

            $clientAbilities = array_values(array_filter(
                $selectedAbilities,
                fn ($ability) => str_contains($ability, '.client.')
            ));

            if ($clientAbilities !== []) {
                $clientViewerAbilities = array_merge(
                    $clientViewerAbilities,
                    array_filter(
                        $clientAbilities,
                        fn ($ability) => str_ends_with($ability, '.view')
                    )
                );

                $clientContributorAbilities = array_merge(
                    $clientContributorAbilities,
                    array_filter(
                        $clientAbilities,
                        fn ($ability) => str_ends_with($ability, '.view')
                            || str_ends_with($ability, '.create')
                            || str_ends_with($ability, '.update')
                    )
                );
            }

            $nonClientAbilities = array_values(array_filter(
                $abilities,
                fn ($ability) => ! str_contains($ability, '.client.')
            ));

            $roleDefinitions = [
                [
                    'suffix' => 'viewer',
                    'name' => "$label Viewer",
                    'filter' => fn ($ability) => str_ends_with($ability, '.view'),
                    'level' => 3,
                ],
            ];

            if ($feature === 'clients') {
                $roleDefinitions[] = [
                    'suffix' => 'contributor',
                    'name' => "$label Contributor",
                    'filter' => fn ($ability) => str_ends_with($ability, '.view')
                        || str_ends_with($ability, '.create')
                        || str_ends_with($ability, '.update'),
                    'level' => 3,
                ];
            } else {
                $roleDefinitions[] = [
                    'suffix' => 'editor',
                    'name' => "$label Editor",
                    'filter' => fn ($ability) => str_ends_with($ability, '.view')
                        || str_ends_with($ability, '.create')
                        || str_ends_with($ability, '.update'),
                    'level' => 3,
                ];
            }

            $roleDefinitions[] = [
                'suffix' => 'manager',
                'name' => "$label Manager",
                'filter' => null,
                'level' => 2,
            ];

            foreach ($roleDefinitions as $definition) {
                $filteredAbilities = $definition['filter']
                    ? array_filter($nonClientAbilities, $definition['filter'])
                    : $nonClientAbilities;

                $roles[] = [
                    'slug' => "{$feature}_{$definition['suffix']}",
                    'name' => $definition['name'],
                    'abilities' => array_values(array_intersect($filteredAbilities, $selected)),
                    'level' => $definition['level'],
                ];
            }
        }

        $clientViewerAbilities = array_values(array_unique($clientViewerAbilities));
        $clientContributorAbilities = array_values(array_unique($clientContributorAbilities));

        $roles[] = [
            'slug' => 'client_viewer',
            'name' => 'Client Viewer',
            'abilities' => $clientViewerAbilities,
            'level' => 5,
        ];

        $roles[] = [
            'slug' => 'client_contributor',
            'name' => 'Client Contributor',
            'abilities' => $clientContributorAbilities,
            'level' => 4,
        ];

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
