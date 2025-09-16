<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TenantListFilterTest extends TestCase
{
    use RefreshDatabase;

    private function actAsSuperAdminForTenant(Tenant $homeTenant, array $abilities = ['tenants.view']): User
    {
        $role = Role::create([
            'name' => 'SuperAdmin',
            'slug' => 'super_admin',
            'tenant_id' => $homeTenant->id,
            'abilities' => $abilities,
            'level' => 0,
        ]);

        $user = User::create([
            'name' => 'Root',
            'email' => 'root@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $homeTenant->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);

        $user->roles()->attach($role->id, ['tenant_id' => $homeTenant->id]);

        Sanctum::actingAs($user);

        return $user;
    }

    public function test_super_admin_can_filter_tenants_by_id(): void
    {
        $tenantA = Tenant::create(['name' => 'Alpha']);
        $tenantB = Tenant::create(['name' => 'Beta']);

        $this->actAsSuperAdminForTenant($tenantA);

        $this->getJson('/api/tenants?tenant_id=' . $tenantB->id)
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'id' => $tenantB->id,
                'name' => 'Beta',
            ])
            ->assertJsonMissing(['id' => $tenantA->id])
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('meta.page', 1)
            ->assertJsonPath('meta.per_page', 15);
    }

    public function test_super_admin_can_search_tenants_by_partial_name(): void
    {
        $tenantA = Tenant::create(['name' => 'Alpha Manufacturing']);
        $tenantB = Tenant::create(['name' => 'Beta Logistics']);
        $tenantC = Tenant::create(['name' => 'Gamma Research']);

        $this->actAsSuperAdminForTenant($tenantA);

        $this->getJson('/api/tenants?search=Beta')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'id' => $tenantB->id,
                'name' => 'Beta Logistics',
            ])
            ->assertJsonMissing(['name' => 'Alpha Manufacturing'])
            ->assertJsonMissing(['name' => 'Gamma Research'])
            ->assertJsonPath('meta.total', 1);

        $this->getJson('/api/tenants?search=Zeta')
            ->assertStatus(200)
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0)
            ->assertJsonPath('meta.page', 1);
    }
}
