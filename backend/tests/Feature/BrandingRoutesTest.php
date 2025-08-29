<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BrandingRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $tenant = Tenant::create(['name' => 'Test Tenant']);
        $role = Role::create([
            'name' => 'SuperAdmin',
            'tenant_id' => $tenant->id,
            'abilities' => json_encode(['*']),
        ]);
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

    public function test_super_admin_can_update_and_get_branding(): void
    {
        $payload = [
            'name' => 'Brand',
            'color' => '#ffffff',
            'secondary_color' => '#000000',
            'color_dark' => '#111111',
            'secondary_color_dark' => '#222222',
            'logo' => 'logo.png',
            'logo_dark' => 'logo-dark.png',
            'footer_left' => 'Left',
            'footer_right' => 'Right',
        ];
        $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->putJson('/api/branding', $payload)
            ->assertStatus(200)
            ->assertJsonPath('footer_left', 'Left')
            ->assertJsonPath('footer_right', 'Right');

        $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->getJson('/api/branding')
            ->assertStatus(200)
            ->assertJsonPath('footer_left', 'Left')
            ->assertJsonPath('footer_right', 'Right')
            ->assertJsonPath('secondary_color', '#000000')
            ->assertJsonPath('color_dark', '#111111')
            ->assertJsonPath('secondary_color_dark', '#222222')
            ->assertJsonPath('logo_dark', 'logo-dark.png');
    }

    public function test_client_admin_cannot_update_branding(): void
    {
        $tenant = Tenant::create(['name' => 'Another Tenant']);
        $role = Role::create([
            'name' => 'ClientAdmin',
            'tenant_id' => $tenant->id,
            'abilities' => json_encode([]),
        ]);
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
            ->putJson('/api/branding', ['footer_left' => 'Nope'])
            ->assertStatus(403);
    }
}

