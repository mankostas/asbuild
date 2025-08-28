<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ThemeSettingsRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_get_theme_settings(): void
    {
        $tenant = Tenant::create(['name' => 'Test Tenant', 'features' => ['themes']]);
        $user = User::create([
            'name' => 'User',
            'email' => 'user@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $role = Role::create([
            'name' => 'SuperAdmin',
            'slug' => 'super_admin',
            'abilities' => ['*'],
        ]);
        $user->roles()->attach($role->id, ['tenant_id' => $tenant->id]);
        Sanctum::actingAs($user);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->getJson('/api/settings/theme')
            ->assertStatus(200)
            ->assertExactJson([]);
    }
}
