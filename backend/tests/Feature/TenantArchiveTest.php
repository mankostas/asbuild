<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TenantArchiveTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsSuperAdmin(array $abilities = ['*']): array
    {
        $homeTenant = Tenant::create(['name' => 'Home Tenant']);

        $role = Role::create([
            'name' => 'SuperAdmin',
            'slug' => 'super_admin',
            'tenant_id' => $homeTenant->id,
            'abilities' => $abilities,
            'level' => 0,
        ]);

        $user = User::create([
            'name' => 'Root User',
            'email' => 'root@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $homeTenant->id,
            'phone' => '1234567890',
            'address' => 'Main Street',
        ]);

        $user->roles()->attach($role->id, ['tenant_id' => $homeTenant->id]);

        Sanctum::actingAs($user);

        return [$user, $homeTenant];
    }

    private function actingAsRegularUser(Tenant $tenant): User
    {
        $user = User::create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '5551112222',
            'address' => 'Side Street',
        ]);

        Sanctum::actingAs($user);

        return $user;
    }

    public function test_super_admin_can_archive_and_unarchive_tenant(): void
    {
        $this->actingAsSuperAdmin(['tenants.update', 'tenants.view']);

        $tenant = Tenant::create(['name' => 'Archive Target']);

        $this->postJson("/api/tenants/{$tenant->id}/archive")
            ->assertStatus(200)
            ->assertJsonFragment(['id' => $tenant->id]);

        $this->assertNotNull($tenant->refresh()->archived_at);

        $this->deleteJson("/api/tenants/{$tenant->id}/archive")
            ->assertStatus(200)
            ->assertJsonFragment(['id' => $tenant->id]);

        $this->assertNull($tenant->refresh()->archived_at);
    }

    public function test_super_admin_can_soft_delete_and_restore_tenant(): void
    {
        $this->actingAsSuperAdmin(['tenants.update', 'tenants.delete']);

        $tenant = Tenant::create(['name' => 'Delete Target']);

        $this->deleteJson("/api/tenants/{$tenant->id}")
            ->assertStatus(204);

        $trashed = Tenant::withTrashed()->find($tenant->id);
        $this->assertNotNull($trashed);
        $this->assertNotNull($trashed->deleted_at);

        $this->postJson("/api/tenants/{$tenant->id}/restore")
            ->assertStatus(200)
            ->assertJsonFragment(['id' => $tenant->id]);

        $restored = Tenant::find($tenant->id);
        $this->assertNotNull($restored);
        $this->assertNull($restored->deleted_at);
        $this->assertNull($restored->archived_at);
    }

    public function test_super_admin_can_bulk_archive_and_restore_tenants(): void
    {
        $this->actingAsSuperAdmin(['tenants.update']);

        $tenantA = Tenant::create(['name' => 'Tenant A']);
        $tenantB = Tenant::create(['name' => 'Tenant B']);
        $tenantC = Tenant::create(['name' => 'Tenant C']);

        $this->postJson('/api/tenants/bulk-archive', ['ids' => [$tenantA->id, $tenantB->id]])
            ->assertStatus(200)
            ->assertJsonCount(2);

        $this->assertNotNull($tenantA->refresh()->archived_at);
        $this->assertNotNull($tenantB->refresh()->archived_at);
        $this->assertNull($tenantC->refresh()->archived_at);

        $tenantB->delete();
        $this->assertNotNull(Tenant::withTrashed()->find($tenantB->id)->deleted_at);

        $this->postJson('/api/tenants/bulk-restore', ['ids' => [$tenantA->id, $tenantB->id]])
            ->assertStatus(200)
            ->assertJsonCount(2);

        $this->assertNull($tenantA->refresh()->archived_at);
        $restoredB = Tenant::find($tenantB->id);
        $this->assertNotNull($restoredB);
        $this->assertNull($restoredB->archived_at);
        $this->assertNull($restoredB->deleted_at);
    }

    public function test_super_admin_can_bulk_destroy_tenants(): void
    {
        $this->actingAsSuperAdmin(['tenants.delete']);

        $tenantA = Tenant::create(['name' => 'Tenant A']);
        $tenantB = Tenant::create(['name' => 'Tenant B']);

        $this->postJson('/api/tenants/bulk-delete', ['ids' => [$tenantA->id, $tenantB->id]])
            ->assertStatus(200)
            ->assertJsonCount(2);

        $this->assertNotNull(Tenant::withTrashed()->find($tenantA->id)->deleted_at);
        $this->assertNotNull(Tenant::withTrashed()->find($tenantB->id)->deleted_at);
    }

    public function test_non_super_admin_cannot_archive_tenant(): void
    {
        [, $homeTenant] = $this->actingAsSuperAdmin(['tenants.update']);

        $targetTenant = Tenant::create(['name' => 'Target Tenant']);

        // Switch to a regular user without SuperAdmin role
        $this->actingAsRegularUser($homeTenant);

        $this->postJson("/api/tenants/{$targetTenant->id}/archive")
            ->assertStatus(403);

        $this->assertNull($targetTenant->refresh()->archived_at);
    }

    public function test_super_admin_can_filter_archived_and_trashed_tenants(): void
    {
        $this->actingAsSuperAdmin(['tenants.view']);

        $active = Tenant::create(['name' => 'Active Tenant']);
        $archived = Tenant::create(['name' => 'Archived Tenant', 'archived_at' => now()]);
        $trashed = Tenant::create(['name' => 'Trashed Tenant']);
        $trashed->delete();

        $this->getJson('/api/tenants?archived=only')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $archived->id]);

        $this->getJson('/api/tenants?archived=all')
            ->assertStatus(200)
            ->assertJsonCount(3, 'data');

        $this->getJson('/api/tenants?trashed=only')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $trashed->id]);
    }
}
