<?php

namespace Tests\Unit;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AbilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

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
        $tenant = Tenant::create(['name' => 'Acme Inc.', 'features' => []]);

        $user = User::create([
            'name' => 'Owner',
            'email' => 'owner@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
        ]);

        $role = Role::create([
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
        $tenant = Tenant::create(['name' => 'Beta LLC', 'features' => []]);

        $user = User::create([
            'name' => 'Manager',
            'email' => 'manager@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
        ]);

        $role = Role::create([
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

    public function test_tenant_specific_abilities_are_isolated(): void
    {
        $tenantOne = Tenant::create(['name' => 'Tenant One', 'features' => []]);
        $tenantTwo = Tenant::create(['name' => 'Tenant Two', 'features' => []]);

        $user = User::create([
            'name' => 'Employee',
            'email' => 'employee@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenantOne->id,
        ]);

        $role = Role::create([
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

    public function test_dashboard_view_alias_grants_reports_view(): void
    {
        $tenant = Tenant::create(['name' => 'Gamma Corp', 'features' => ['dashboard']]);

        $user = User::create([
            'name' => 'Analyst',
            'email' => 'analyst@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
        ]);

        $role = Role::create([
            'tenant_id' => $tenant->id,
            'name' => 'Viewer',
            'slug' => 'viewer',
            'level' => 3,
            'abilities' => ['dashboard.view'],
        ]);

        $role->users()->attach($user->id, ['tenant_id' => $tenant->id]);

        $this->assertTrue($this->service->userHasAbility($user, 'reports.view'));
        $this->assertTrue($this->service->userHasAbility($user, 'dashboard.view'));
        $this->assertContains('reports.view', $this->service->resolveAbilities($user));
    }
}
