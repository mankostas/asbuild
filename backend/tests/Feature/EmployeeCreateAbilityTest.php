<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use App\Services\AbilityService;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use App\Support\PublicIdGenerator;

class EmployeeCreateAbilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_employees_create_cannot_create_employee(): void
    {
        $tenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Tenant', 'features' => ['employees']
        ]);

        $role = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Viewer',
            'slug' => 'viewer',
            'tenant_id' => $tenant->id,
            'abilities' => ['employees.view'],
            'level' => 3,
        ]);

        $user = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Viewer User',
            'email' => 'viewer@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '1234567890',
            'address' => '1 Street',
        ]);

        $user->roles()->attach($role->id, ['tenant_id' => $tenant->id]);
        $user->refresh();
        Sanctum::actingAs($user);

        Password::shouldReceive('sendResetLink')->never();

        $payload = [
            'name' => 'New Employee',
            'email' => 'employee@example.com',
        ];

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/employees', $payload)
            ->assertStatus(403);
    }

    public function test_user_with_employees_create_can_create_employee(): void
    {
        $tenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Tenant', 'features' => ['employees']
        ]);

        $role = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Editor',
            'slug' => 'editor',
            'tenant_id' => $tenant->id,
            'abilities' => ['employees.create'],
            'level' => 2,
        ]);

        $user = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Editor User',
            'email' => 'editor@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '0987654321',
            'address' => '2 Street',
        ]);

        $user->roles()->attach($role->id, ['tenant_id' => $tenant->id]);
        $user->refresh();
        Sanctum::actingAs($user);

        Password::shouldReceive('sendResetLink')
            ->once()
            ->andReturn(Password::RESET_LINK_SENT);

        $this->assertTrue(
            app(AbilityService::class)->userHasAbility($user->fresh(), 'employees.create', $tenant->id)
        );

        $payload = [
            'name' => 'Created Employee',
            'email' => 'created@example.com',
        ];

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/employees', $payload)
            ->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'email' => 'created@example.com',
            'tenant_id' => $tenant->id,
            'type' => 'employee',
        ]);
    }
}

