<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_assignment_persists(): void
    {
        $tenant = Tenant::create(['name' => 'Tenant']);
        $adminRole = Role::create([
            'name' => 'ClientAdmin',
            'slug' => 'client_admin',
            'tenant_id' => $tenant->id,
            'abilities' => ['roles.manage'],
        ]);
        $role = Role::create(['name' => 'Tester', 'slug' => 'tester', 'tenant_id' => $tenant->id]);

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $user = User::create([
            'name' => 'User',
            'email' => 'user@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $admin->roles()->attach($adminRole->id, ['tenant_id' => $tenant->id]);
        Sanctum::actingAs($admin);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson("/api/roles/{$role->id}/assign", ['user_id' => $user->id])
            ->assertStatus(200);

        $this->assertTrue(
            $user->roles()->where('roles.id', $role->id)->wherePivot('tenant_id', $tenant->id)->exists()
        );
    }
}
