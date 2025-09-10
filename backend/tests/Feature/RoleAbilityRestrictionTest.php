<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RoleAbilityRestrictionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_roles_view_can_list_but_not_modify_roles(): void
    {
        $tenant = Tenant::create(['name' => 'Tenant']);

        $viewRole = Role::create([
            'name' => 'Viewer',
            'slug' => 'viewer',
            'tenant_id' => $tenant->id,
            'abilities' => ['roles.view'],
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

        $user->roles()->attach($viewRole->id, ['tenant_id' => $tenant->id]);
        Sanctum::actingAs($user);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->getJson('/api/roles')
            ->assertStatus(200);

        $payload = ['name' => 'New', 'slug' => 'new', 'level' => 1];
        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/roles', $payload)
            ->assertStatus(403);
    }
}
