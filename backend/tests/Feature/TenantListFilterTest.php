<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use App\Support\PublicIdGenerator;

class TenantListFilterTest extends TestCase
{
    use RefreshDatabase;

    private function actAsSuperAdminForTenant(Tenant $homeTenant, array $abilities = ['tenants.view']): User
    {
        $role = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'SuperAdmin',
            'slug' => 'super_admin',
            'tenant_id' => $homeTenant->id,
            'abilities' => $abilities,
            'level' => 0,
        ]);

        $user = User::create([
            'public_id' => PublicIdGenerator::generate(),
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
        $tenantA = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Alpha'
        ]);
        $tenantB = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Beta'
        ]);

        $this->actAsSuperAdminForTenant($tenantA);

        $this->getJson('/api/tenants?tenant_id=' . $this->publicIdFor($tenantB))
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'public_id' => $this->publicIdFor($tenantB),
                'name' => 'Beta',
            ])
            ->assertJsonMissing(['public_id' => $this->publicIdFor($tenantA)])
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('meta.page', 1)
            ->assertJsonPath('meta.per_page', 15);
    }

    public function test_super_admin_can_search_tenants_by_partial_name(): void
    {
        $tenantA = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Alpha Manufacturing'
        ]);
        $tenantB = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Beta Logistics'
        ]);
        $tenantC = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Gamma Research'
        ]);

        $this->actAsSuperAdminForTenant($tenantA);

        $this->getJson('/api/tenants?search=Beta')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'public_id' => $this->publicIdFor($tenantB),
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
