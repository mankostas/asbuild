<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FooterSettingsRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $tenant = Tenant::create(['name' => 'Test Tenant']);
        $role = Role::create(['name' => 'SuperAdmin', 'tenant_id' => $tenant->id]);
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $user->roles()->attach($role->id, ['tenant_id' => $tenant->id]);
        Sanctum::actingAs($user);
        $this->tenant = $tenant;
    }

    public function test_super_admin_can_update_and_get_footer(): void
    {
        $payload = ['text' => 'New footer'];
        $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->putJson('/api/settings/footer', $payload)
            ->assertStatus(200)
            ->assertJsonPath('text', 'New footer');

        $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->getJson('/api/settings/footer')
            ->assertStatus(200)
            ->assertJsonPath('text', 'New footer');
    }

    public function test_client_admin_cannot_update_footer(): void
    {
        $tenant = Tenant::create(['name' => 'Another Tenant']);
        $role = Role::create(['name' => 'ClientAdmin', 'tenant_id' => $tenant->id]);
        $user = User::create([
            'name' => 'Client',
            'email' => 'client@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $user->roles()->attach($role->id, ['tenant_id' => $tenant->id]);
        Sanctum::actingAs($user);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->putJson('/api/settings/footer', ['text' => 'Forbidden'])
            ->assertStatus(403);
    }
}

