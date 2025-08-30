<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DefaultFeatureRolesSeeder extends Seeder
{
    public static function syncDefaultRolesForFeatures(Tenant $tenant): void
    {
        $features = $tenant->features ?? [];
        $map = config('feature_map', []);

        $roles = [];

        foreach ($features as $feature) {
            switch ($feature) {
                case 'tasks':
                case 'notifications':
                case 'types':
                case 'teams':
                case 'statuses':
                case 'employees':
                case 'themes':
                case 'tenants':
                    $uc = ucfirst($feature);
                    // viewer role
                    $roles[] = [
                        'slug' => "$feature\_viewer",
                        'name' => "$uc Viewer",
                        'abilities' => ["$feature.view"],
                        'level' => 3,
                    ];

                    // editor role
                    $abilities = ["$feature.view"];
                    if (in_array("$feature.create", $map[$feature]['abilities'] ?? [])) {
                        $abilities[] = "$feature.create";
                    }
                    if (in_array("$feature.update", $map[$feature]['abilities'] ?? [])) {
                        $abilities[] = "$feature.update";
                    }
                    $roles[] = [
                        'slug' => "$feature\_editor",
                        'name' => "$uc Editor",
                        'abilities' => $abilities,
                        'level' => 3,
                    ];

                    // manager role
                    $abilities = [];
                    if (in_array("$feature.manage", $map[$feature]['abilities'] ?? [])) {
                        $abilities[] = "$feature.manage";
                    }
                    if (in_array("$feature.delete", $map[$feature]['abilities'] ?? [])) {
                        $abilities[] = "$feature.delete";
                    }
                    if (in_array("$feature.assign", $map[$feature]['abilities'] ?? [])) {
                        $abilities[] = "$feature.assign";
                    }
                    $roles[] = [
                        'slug' => "$feature\_manager",
                        'name' => "$uc Manager",
                        'abilities' => $abilities,
                        'level' => 2,
                    ];
                    break;
                case 'gdpr':
                    $roles[] = [
                        'slug' => 'gdpr_viewer',
                        'name' => 'GDPR Viewer',
                        'abilities' => ['gdpr.view'],
                        'level' => 3,
                    ];
                    $roles[] = [
                        'slug' => 'gdpr_manager',
                        'name' => 'GDPR Manager',
                        'abilities' => ['gdpr.view', 'gdpr.manage', 'gdpr.export', 'gdpr.delete'],
                        'level' => 2,
                    ];
                    break;
                case 'reports':
                    $roles[] = [
                        'slug' => 'reports_viewer',
                        'name' => 'Reports Viewer',
                        'abilities' => ['reports.view'],
                        'level' => 3,
                    ];
                    $roles[] = [
                        'slug' => 'reports_manager',
                        'name' => 'Reports Manager',
                        'abilities' => ['reports.manage'],
                        'level' => 2,
                    ];
                    break;
                case 'roles':
                    $roles[] = [
                        'slug' => 'roles_manager',
                        'name' => 'Roles Manager',
                        'abilities' => ['roles.view', 'roles.manage'],
                        'level' => 2,
                    ];
                    break;
            }
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
