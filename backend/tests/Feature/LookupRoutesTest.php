<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LookupRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $tenant = Tenant::create(['name' => 'Test Tenant']);
        $role = Role::create([
            'name' => 'ClientAdmin',
            'slug' => 'client_admin',
            'tenant_id' => $tenant->id,
            'abilities' => ['roles.manage'],
        ]);
        $user = User::create([
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $user->roles()->attach($role->id, ['tenant_id' => $tenant->id]);
        Sanctum::actingAs($user);
        $this->tenant = $tenant;
    }

    public function test_abilities_lookup_returns_list(): void
    {
        $abilities = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->getJson('/api/lookups/abilities')
            ->assertStatus(200)
            ->json();

        $this->assertContains('roles.manage', $abilities);
    }

    public function test_abilities_lookup_scopes_for_tenant_features(): void
    {
        $this->tenant->update(['features' => ['roles', 'teams']]);

        $abilities = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->getJson('/api/lookups/abilities?forTenant=1')
            ->assertStatus(200)
            ->json();

        $this->assertEqualsCanonicalizing(['roles.manage', 'teams.manage'], $abilities);
    }

    public function test_abilities_lookup_for_super_admin_returns_full_list(): void
    {
        $rootTenant = Tenant::create(['name' => 'Root']);
        $superRole = Role::create([
            'name' => 'SuperAdmin',
            'tenant_id' => $rootTenant->id,
            'level' => 0,
        ]);
        $superUser = User::create([
            'name' => 'Super',
            'email' => 'super@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $rootTenant->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $superUser->roles()->attach($superRole->id, ['tenant_id' => $rootTenant->id]);
        Sanctum::actingAs($superUser);

        $abilities = $this->getJson('/api/lookups/abilities?forTenant=1')
            ->assertStatus(200)
            ->json();

        $this->assertEqualsCanonicalizing(config('abilities'), $abilities);
    }

    public function test_features_lookup_returns_list(): void
    {
        $features = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->getJson('/api/lookups/features')
            ->assertStatus(200)
            ->json();

        $this->assertContains(['slug' => 'appointments', 'label' => 'Appointments'], $features);
        $this->assertContains(['slug' => 'roles', 'label' => 'Roles & Permissions'], $features);
        $this->assertContains(['slug' => 'types', 'label' => 'Appointment Types'], $features);
        $this->assertContains(['slug' => 'teams', 'label' => 'Teams'], $features);
        $this->assertContains(['slug' => 'statuses', 'label' => 'Statuses'], $features);
    }
}

