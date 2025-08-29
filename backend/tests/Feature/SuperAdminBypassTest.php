<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SuperAdminBypassTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_assign_any_ability(): void
    {
        $tenant = Tenant::create(['name' => 'Tenant', 'features' => ['tasks']]);
        $superRole = Role::create([
            'name' => 'SuperAdmin',
            'slug' => 'super_admin',
            'tenant_id' => null,
            'level' => 0,
            'abilities' => ['roles.manage'],
        ]);

        $user = User::create([
            'name' => 'Root',
            'email' => 'root@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $user->roles()->attach($superRole->id, ['tenant_id' => $tenant->id]);

        Sanctum::actingAs($user);

        $payload = [
            'name' => 'Types Manager',
            'slug' => 'task_types.manager',
            'abilities' => ['task_types.manage'],
            'tenant_id' => $tenant->id,
            'level' => 1,
        ];

        $this->postJson('/api/roles', $payload)
            ->assertStatus(201)
            ->assertJsonFragment(['abilities' => ['task_types.manage']]);
    }
}

