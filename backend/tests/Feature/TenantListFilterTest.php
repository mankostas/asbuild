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

    public function test_super_admin_can_filter_tenants_by_id(): void
    {
        $tenantA = Tenant::create(['name' => 'Alpha']);
        $tenantB = Tenant::create(['name' => 'Beta']);

        $superRole = Role::create([
            'name' => 'SuperAdmin',
            'slug' => 'super_admin',
            'tenant_id' => $tenantA->id,
        ]);

        $user = User::create([
            'name' => 'Root',
            'email' => 'root@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenantA->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);

        $user->roles()->attach($superRole->id, ['tenant_id' => $tenantA->id]);

        Sanctum::actingAs($user);

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
}
