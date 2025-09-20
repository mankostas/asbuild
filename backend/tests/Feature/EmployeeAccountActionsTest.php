<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EmployeeAccountActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_reset_requires_manage_ability(): void
    {
        [$tenant, $admin] = $this->createTenantAdmin(['employees.view']);
        $employee = $this->createEmployee($tenant, 'Agent', 'agent@example.com');

        Sanctum::actingAs($admin);

        Password::shouldReceive('sendResetLink')->never();

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson("/api/employees/{$employee->id}/password-reset")
            ->assertStatus(403);
    }

    public function test_password_reset_dispatches_notification(): void
    {
        [$tenant, $admin] = $this->createTenantAdmin(['employees.manage']);
        $employee = $this->createEmployee($tenant, 'Agent', 'agent@example.com');

        Sanctum::actingAs($admin);

        Password::shouldReceive('sendResetLink')
            ->once()
            ->with(['email' => $employee->email])
            ->andReturn(Password::RESET_LINK_SENT);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson("/api/employees/{$employee->id}/password-reset")
            ->assertStatus(200)
            ->assertJson(['status' => 'ok']);
    }

    public function test_invite_resend_dispatches_notification(): void
    {
        [$tenant, $admin] = $this->createTenantAdmin(['employees.manage']);
        $employee = $this->createEmployee($tenant, 'Agent', 'agent@example.com');

        Sanctum::actingAs($admin);

        Password::shouldReceive('sendResetLink')
            ->once()
            ->with(['email' => $employee->email])
            ->andReturn(Password::RESET_LINK_SENT);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson("/api/employees/{$employee->id}/invite-resend")
            ->assertStatus(200)
            ->assertJson(['status' => 'ok']);
    }

    public function test_email_reset_updates_address_and_sends_notification(): void
    {
        [$tenant, $admin] = $this->createTenantAdmin(['employees.manage']);
        $employee = $this->createEmployee($tenant, 'Agent', 'agent@example.com');

        Sanctum::actingAs($admin);

        Password::shouldReceive('sendResetLink')
            ->once()
            ->with(['email' => 'updated@example.com'])
            ->andReturn(Password::RESET_LINK_SENT);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson("/api/employees/{$employee->id}/email-reset", [
                'email' => 'updated@example.com',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.email', 'updated@example.com');

        $this->assertDatabaseHas('users', [
            'id' => $employee->id,
            'email' => 'updated@example.com',
        ]);
    }

    /**
     * @return array{0: Tenant, 1: User, tenant_public_id: string, admin_public_id: string}
     */
    protected function createTenantAdmin(array $abilities): array
    {
        $tenant = Tenant::create(['name' => 'Tenant', 'features' => ['employees']]);

        $role = Role::create([
            'name' => 'Admin',
            'slug' => 'admin',
            'tenant_id' => $tenant->id,
            'abilities' => $abilities,
        ]);

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '1234567890',
            'address' => '1 Admin Street',
            'type' => 'employee',
            'status' => 'active',
        ]);

        $role->users()->attach($admin->id, ['tenant_id' => $tenant->id]);

        return [
            $tenant,
            $admin,
            'tenant_public_id' => $this->publicIdFor($tenant),
            'admin_public_id' => $this->publicIdFor($admin),
        ];
    }

    protected function createEmployee(Tenant $tenant, string $name, string $email): User
    {
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '5550000000',
            'address' => '1 Employee Street',
            'type' => 'employee',
            'status' => 'active',
        ]);
    }
}
