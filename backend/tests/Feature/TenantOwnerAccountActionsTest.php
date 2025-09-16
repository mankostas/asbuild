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

class TenantOwnerAccountActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_endpoint_returns_owner_metadata(): void
    {
        [$tenant, $owner, $admin] = $this->createTenantWithOwner(['tenants.view']);

        Sanctum::actingAs($admin);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->getJson("/api/tenants/{$tenant->id}/owner")
            ->assertStatus(200)
            ->assertJsonPath('data.email', $owner->email)
            ->assertJsonPath('data.id', $owner->id);
    }

    public function test_owner_password_reset_requires_manage_ability(): void
    {
        [$tenant, $owner, $admin] = $this->createTenantWithOwner(['tenants.view']);

        Sanctum::actingAs($admin);

        Password::shouldReceive('sendResetLink')->never();

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson("/api/tenants/{$tenant->id}/owner/password-reset")
            ->assertStatus(403);
    }

    public function test_owner_password_reset_dispatches_notification(): void
    {
        [$tenant, $owner, $admin] = $this->createTenantWithOwner(['tenants.manage']);

        Sanctum::actingAs($admin);

        Password::shouldReceive('sendResetLink')
            ->once()
            ->with(['email' => $owner->email])
            ->andReturn(Password::RESET_LINK_SENT);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson("/api/tenants/{$tenant->id}/owner/password-reset")
            ->assertStatus(200)
            ->assertJson(['status' => 'ok']);
    }

    public function test_owner_invite_resend_dispatches_notification(): void
    {
        [$tenant, $owner, $admin] = $this->createTenantWithOwner(['tenants.manage']);

        Sanctum::actingAs($admin);

        Password::shouldReceive('sendResetLink')
            ->once()
            ->with(['email' => $owner->email])
            ->andReturn(Password::RESET_LINK_SENT);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson("/api/tenants/{$tenant->id}/owner/invite-resend")
            ->assertStatus(200)
            ->assertJson(['status' => 'ok']);
    }

    public function test_owner_email_reset_updates_address_and_sends_notification(): void
    {
        [$tenant, $owner, $admin] = $this->createTenantWithOwner(['tenants.manage']);

        Sanctum::actingAs($admin);

        Password::shouldReceive('sendResetLink')
            ->once()
            ->with(['email' => 'new-owner@example.com'])
            ->andReturn(Password::RESET_LINK_SENT);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson("/api/tenants/{$tenant->id}/owner/email-reset", [
                'email' => 'new-owner@example.com',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.email', 'new-owner@example.com');

        $this->assertDatabaseHas('users', [
            'id' => $owner->id,
            'email' => 'new-owner@example.com',
        ]);
    }

    /**
     * @return array{0: Tenant, 1: User, 2: User}
     */
    protected function createTenantWithOwner(array $adminAbilities): array
    {
        $tenant = Tenant::create(['name' => 'Tenant', 'features' => ['employees']]);

        $ownerRole = $tenant->roles()->where('slug', 'tenant')->first();

        if ($ownerRole) {
            $ownerRole->update(['abilities' => ['tenants.manage']]);
        } else {
            $ownerRole = Role::create([
                'name' => 'Tenant Owner',
                'slug' => 'tenant',
                'tenant_id' => $tenant->id,
                'abilities' => ['tenants.manage'],
            ]);
        }

        $owner = User::create([
            'name' => 'Owner',
            'email' => 'owner@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '5550001000',
            'address' => '1 Owner Street',
            'type' => 'tenant',
            'status' => 'active',
        ]);

        $ownerRole->users()->attach($owner->id, ['tenant_id' => $tenant->id]);

        $adminRole = Role::create([
            'name' => 'Tenant Admin',
            'slug' => 'tenant_admin',
            'tenant_id' => $tenant->id,
            'abilities' => $adminAbilities,
        ]);

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '5550002000',
            'address' => '2 Admin Street',
            'type' => 'employee',
            'status' => 'active',
        ]);

        $adminRole->users()->attach($admin->id, ['tenant_id' => $tenant->id]);

        return [$tenant, $owner, $admin];
    }
}
