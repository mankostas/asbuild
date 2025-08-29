<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SuperAdminFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_has_all_features_even_when_tenant_does_not(): void
    {
        $tenant = Tenant::create(['name' => 'Tenant', 'features' => []]);

        $role = Role::create([
            'name' => 'SuperAdmin',
            'slug' => 'super_admin',
            'tenant_id' => $tenant->id,
            'abilities' => ['*'],
            'level' => 0,
        ]);

        $user = User::create([
            'name' => 'Super',
            'email' => 'super@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $user->roles()->attach($role->id, ['tenant_id' => $tenant->id]);

        Sanctum::actingAs($user);

        $data = $this->getJson('/api/me')->assertStatus(200)->json();

        $this->assertEqualsCanonicalizing(config('features'), $data['features']);
        $this->assertContains('notifications', $data['features']);
        $this->assertContains('branding', $data['features']);
        $this->assertContains('themes', $data['features']);
    }
}

