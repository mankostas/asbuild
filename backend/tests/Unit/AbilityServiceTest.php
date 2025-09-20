<?php

namespace Tests\Unit;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AbilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use App\Support\PublicIdGenerator;

class AbilityServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AbilityService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(AbilityService::class);
    }

    public function test_user_with_wildcard_has_any_ability(): void
    {
        $tenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Acme Inc.', 'features' => []
        ]);

        $user = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Owner',
            'email' => 'owner@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
        ]);

        $role = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => $tenant->id,
            'name' => 'Owner',
            'slug' => 'owner',
            'level' => 1,
            'abilities' => ['*'],
        ]);

        $role->users()->attach($user->id, ['tenant_id' => $tenant->id]);

        $this->assertTrue($this->service->userHasAbility($user, 'tasks.update'));
        $this->assertTrue($this->service->userHasAnyAbility($user, ['foo.bar', 'baz.qux']));
    }

    public function test_manage_prefix_grants_related_permissions(): void
    {
        $tenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Beta LLC', 'features' => []
        ]);

        $user = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Manager',
            'email' => 'manager@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
        ]);

        $role = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => $tenant->id,
            'name' => 'Manager',
            'slug' => 'manager',
            'level' => 2,
            'abilities' => ['tasks.manage'],
        ]);

        $role->users()->attach($user->id, ['tenant_id' => $tenant->id]);

        $this->assertTrue($this->service->userHasAbility($user, 'tasks.update'));
        $this->assertFalse($this->service->userHasAbility($user, 'reports.view'));
    }

    public function test_client_scoped_abilities_match_core_checks(): void
    {
        $tenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Client Tenant', 'features' => ['dashboard', 'tasks', 'reports']
        ]);

        $user = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Client User',
            'email' => 'client@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
        ]);

        $role = Role::where('tenant_id', $tenant->id)
            ->where('slug', 'client_contributor')
            ->firstOrFail();

        $this->assertEqualsCanonicalizing([
            'dashboard.client.view',
            'tasks.client.view',
            'tasks.client.create',
            'tasks.client.update',
            'reports.client.view',
        ], $role->abilities);

        $role->users()->attach($user->id, ['tenant_id' => $tenant->id]);

        $this->assertTrue($this->service->userHasAbility($user, 'dashboard.view'));
        $this->assertTrue($this->service->userHasAbility($user, 'tasks.view'));
        $this->assertTrue($this->service->userHasAbility($user, 'tasks.create'));
        $this->assertTrue($this->service->userHasAbility($user, 'tasks.update'));
        $this->assertTrue($this->service->userHasAbility($user, 'reports.view'));

        $this->assertFalse($this->service->userHasAbility($user, 'tasks.delete'));
        $this->assertFalse($this->service->userHasAbility($user, 'tasks.manage'));
    }

    public function test_tenant_specific_abilities_are_isolated(): void
    {
        $tenantOne = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Tenant One', 'features' => []
        ]);
        $tenantTwo = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Tenant Two', 'features' => []
        ]);

        $user = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Employee',
            'email' => 'employee@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenantOne->id,
        ]);

        $role = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => $tenantOne->id,
            'name' => 'Analyst',
            'slug' => 'analyst',
            'level' => 3,
            'abilities' => ['tasks.view'],
        ]);

        $role->users()->attach($user->id, ['tenant_id' => $tenantOne->id]);

        $this->assertTrue($this->service->userHasAbility($user, 'tasks.view', $tenantOne->id));
        $this->assertFalse($this->service->userHasAbility($user, 'tasks.view', $tenantTwo->id));
    }

    public function test_reports_view_alias_grants_dashboard_view(): void
    {
        $tenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Gamma Corp', 'features' => ['dashboard']
        ]);

        $user = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Analyst',
            'email' => 'analyst@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
        ]);

        $role = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => $tenant->id,
            'name' => 'Viewer',
            'slug' => 'viewer',
            'level' => 3,
            'abilities' => ['reports.view'],
        ]);

        $role->users()->attach($user->id, ['tenant_id' => $tenant->id]);

        $this->assertTrue($this->service->userHasAbility($user, 'dashboard.view'));
        $this->assertTrue($this->service->userHasAbility($user, 'reports.view'));
        $this->assertContains('dashboard.view', $this->service->resolveAbilities($user));
    }

    public function test_tenants_manage_grants_sensitive_actions(): void
    {
        $tenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Delta Group', 'features' => []
        ]);

        $manager = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Manager',
            'email' => 'manager@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
        ]);

        $manageRole = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => $tenant->id,
            'name' => 'Tenant Manager',
            'slug' => 'tenant_manager',
            'level' => 1,
            'abilities' => ['tenants.manage'],
        ]);

        $manageRole->users()->attach($manager->id, ['tenant_id' => $tenant->id]);

        $this->assertTrue($this->service->userHasAbility($manager, 'tenants.delete', $tenant->id));
        $this->assertTrue($this->service->userHasAbility($manager, 'tenants.update', $tenant->id));
        $this->assertTrue($this->service->userHasAbility($manager, 'tenants.impersonate', $tenant->id));

        $viewer = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Viewer',
            'email' => 'viewer@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
        ]);

        $viewRole = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => $tenant->id,
            'name' => 'Tenant Viewer',
            'slug' => 'tenant_viewer',
            'level' => 2,
            'abilities' => ['tenants.view'],
        ]);

        $viewRole->users()->attach($viewer->id, ['tenant_id' => $tenant->id]);

        $this->assertFalse($this->service->userHasAbility($viewer, 'tenants.impersonate', $tenant->id));
        $this->assertFalse($this->service->userHasAnyAbility($viewer, ['tenants.manage', 'tenants.impersonate'], $tenant->id));
    }
}
