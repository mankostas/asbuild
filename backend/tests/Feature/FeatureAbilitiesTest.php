<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FeatureAbilitiesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @dataProvider featureAbilityProvider
     */
    public function test_feature_abilities_enforced(string $feature, string $route, string $ability): void
    {
        $tenant = Tenant::create(['name' => 'Tenant', 'features' => [$feature]]);
        $baseRole = Role::create([
            'name' => 'User',
            'slug' => 'user',
            'tenant_id' => $tenant->id,
            'abilities' => [],
            'level' => 1,
        ]);
        $user = User::create([
            'name' => 'User',
            'email' => 'user@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $user->roles()->attach($baseRole->id, ['tenant_id' => $tenant->id]);
        Sanctum::actingAs($user);

        // Missing ability
        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->getJson($route)
            ->assertStatus(403);

        // Grant ability via additional role
        $abilityRole = Role::create([
            'name' => 'Ability',
            'slug' => 'ability',
            'tenant_id' => $tenant->id,
            'abilities' => [$ability],
            'level' => 1,
        ]);
        $user->roles()->attach($abilityRole->id, ['tenant_id' => $tenant->id]);
        $user->refresh();

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->getJson($route)
            ->assertStatus(200);

        \App\Models\Tenant::setCurrent(null);
        config()->set('tenant', []);
    }

    public static function featureAbilityProvider(): array
    {
        return [
            'gdpr' => ['gdpr', '/api/gdpr/consents', 'gdpr.view'],
            'notifications' => ['notifications', '/api/notifications', 'notifications.view'],
            'roles' => ['roles', '/api/roles', 'roles.manage'],
            'types' => ['types', '/api/appointment-types', 'types.view'],
            'teams' => ['teams', '/api/teams', 'teams.view'],
            'statuses' => ['statuses', '/api/statuses', 'statuses.view'],
        ];
    }
}
