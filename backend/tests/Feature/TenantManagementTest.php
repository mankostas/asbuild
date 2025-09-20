<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DefaultFeatureRolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use App\Support\PublicIdGenerator;

class TenantManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Tenant, user_public_id: string, tenant_public_id: string}
     */
    private function actingAsSuperAdmin(): array
    {
        $homeTenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Home Tenant',
            'features' => ['tenants'],
            'feature_abilities' => ['tenants' => ['tenants.manage']],
        ]);

        DefaultFeatureRolesSeeder::syncDefaultRolesForFeatures($homeTenant, $homeTenant->selectedFeatureAbilities());

        $role = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'SuperAdmin',
            'slug' => 'super_admin',
            'tenant_id' => $homeTenant->id,
            'abilities' => ['*'],
            'level' => 0,
        ]);

        $user = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Root User',
            'email' => 'root@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $homeTenant->id,
            'phone' => '5551234567',
            'address' => 'Main Street',
        ]);

        $user->roles()->attach($role->id, ['tenant_id' => $homeTenant->id]);

        Sanctum::actingAs($user);

        return [
            $user,
            $homeTenant,
            'user_public_id' => $this->publicIdFor($user),
            'tenant_public_id' => $this->publicIdFor($homeTenant),
        ];
    }

    public function test_super_admin_creating_tenant_sends_reset_link_when_notify_owner_enabled(): void
    {
        $this->actingAsSuperAdmin();

        Password::shouldReceive('sendResetLink')
            ->once()
            ->with(['email' => 'owner@example.com'])
            ->andReturn('passwords.sent');

        $response = $this->postJson('/api/tenants', [
            'name' => 'Acme Corp',
            'user_name' => 'Owner One',
            'user_email' => 'owner@example.com',
            'quota_storage_mb' => 0,
            'features' => ['tasks'],
            'feature_abilities' => [
                'tasks' => [
                    'tasks.view',
                    'tasks.create',
                    'tasks.update',
                    'tasks.client.view',
                    'tasks.client.create',
                    'tasks.client.update',
                ],
            ],
        ])->assertCreated()
            ->assertJsonPath('name', 'Acme Corp')
            ->assertJsonPath('feature_abilities.tasks', [
                'tasks.view',
                'tasks.create',
                'tasks.update',
                'tasks.client.view',
                'tasks.client.create',
                'tasks.client.update',
            ]);

        $tenantId = $response->json('id');

        $this->assertDatabaseHas('roles', [
            'tenant_id' => $tenantId,
            'slug' => 'tasks_manager',
        ]);

        $clientViewerAbilities = json_decode((string) DB::table('roles')
            ->where('tenant_id', $tenantId)
            ->where('slug', 'client_viewer')
            ->value('abilities'), true);

        $this->assertEquals(['tasks.client.view'], $clientViewerAbilities);

        $clientContributorAbilities = json_decode((string) DB::table('roles')
            ->where('tenant_id', $tenantId)
            ->where('slug', 'client_contributor')
            ->value('abilities'), true);

        $this->assertEquals([
            'tasks.client.view',
            'tasks.client.create',
            'tasks.client.update',
        ], $clientContributorAbilities);
    }

    public function test_super_admin_creating_tenant_skips_reset_link_when_notify_owner_disabled(): void
    {
        $this->actingAsSuperAdmin();

        Password::shouldReceive('sendResetLink')->never();

        $response = $this->postJson('/api/tenants', [
            'name' => 'Beta LLC',
            'user_name' => 'Owner Two',
            'user_email' => 'owner2@example.com',
            'quota_storage_mb' => 0,
            'features' => ['reports'],
            'feature_abilities' => [
                'reports' => ['reports.manage'],
            ],
            'notify_owner' => false,
        ])->assertCreated()
            ->assertJsonPath('feature_abilities.reports', ['reports.manage']);

        $tenantId = $response->json('id');

        $this->assertDatabaseHas('roles', [
            'tenant_id' => $tenantId,
            'slug' => 'reports_manager',
        ]);

        $tenantRoleAbilities = json_decode((string) DB::table('roles')
            ->where('tenant_id', $tenantId)
            ->where('slug', 'tenant')
            ->value('abilities'), true);

        $this->assertContains('reports.manage', $tenantRoleAbilities);
    }

    public function test_super_admin_updating_tenant_resyncs_default_roles(): void
    {
        $this->actingAsSuperAdmin();

        Password::shouldReceive('sendResetLink')->never();

        $tenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Gamma Inc',
            'features' => ['tasks'],
            'feature_abilities' => [
                'tasks' => ['tasks.view', 'tasks.client.view'],
            ],
            'quota_storage_mb' => 512,
        ]);

        DefaultFeatureRolesSeeder::syncDefaultRolesForFeatures($tenant, $tenant->selectedFeatureAbilities());

        $this->putJson("/api/tenants/{$tenant->id}", [
            'features' => ['tasks', 'clients'],
            'feature_abilities' => [
                'tasks' => ['tasks.view', 'tasks.update'],
                'clients' => ['clients.view', 'clients.update'],
            ],
        ])->assertOk()
            ->assertJsonPath('feature_abilities.clients', ['clients.view', 'clients.update']);

        $clientsManager = json_decode((string) DB::table('roles')
            ->where('tenant_id', $tenant->id)
            ->where('slug', 'clients_manager')
            ->value('abilities'), true);

        $this->assertEquals(['clients.view', 'clients.update'], $clientsManager);

        $tasksManager = json_decode((string) DB::table('roles')
            ->where('tenant_id', $tenant->id)
            ->where('slug', 'tasks_manager')
            ->value('abilities'), true);

        $this->assertEquals(['tasks.view', 'tasks.update'], $tasksManager);
    }
}

