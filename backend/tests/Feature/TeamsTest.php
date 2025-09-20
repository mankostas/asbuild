<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Team;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TeamsTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_membership_sync_requires_manage_ability(): void
    {
        [$tenant, $admin] = $this->createTenantUser(['teams.update']);
        $team = Team::create(['tenant_id' => $tenant->id, 'name' => 'Alpha', 'description' => '']);
        $member = $this->createEmployee($tenant, 'Member', 'member@example.com');

        Sanctum::actingAs($admin);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson("/api/teams/{$team->id}/employees", [
                'employee_ids' => [$member->id],
            ])
            ->assertStatus(403);

        $this->assertSame(0, $team->employees()->count());
    }

    public function test_team_membership_sync_allows_manage_ability(): void
    {
        [$tenant, $admin] = $this->createTenantUser(['teams.manage']);
        $team = Team::create(['tenant_id' => $tenant->id, 'name' => 'Beta', 'description' => '']);
        $memberOne = $this->createEmployee($tenant, 'Member One', 'member1@example.com');
        $memberTwo = $this->createEmployee($tenant, 'Member Two', 'member2@example.com');

        Sanctum::actingAs($admin);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson("/api/teams/{$team->id}/employees", [
                'employee_ids' => [$memberOne->id, $memberTwo->id],
            ])
            ->assertStatus(200);

        $this->assertEqualsCanonicalizing(
            [$memberOne->id, $memberTwo->id],
            $team->fresh()->employees->pluck('id')->all()
        );
    }

    /**
     * @return array{0: Tenant, 1: User, tenant_public_id: string, admin_public_id: string}
     */
    protected function createTenantUser(array $abilities): array
    {
        $tenant = Tenant::create(['name' => 'Tenant']);
        $role = Role::create([
            'name' => 'ClientAdmin',
            'slug' => 'client_admin',
            'tenant_id' => $tenant->id,
            'abilities' => $abilities,
        ]);

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin' . uniqid() . '@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '123456',
            'address' => 'Street 1',
            'type' => 'employee',
            'status' => 'active',
        ]);

        $admin->roles()->attach($role->id, ['tenant_id' => $tenant->id]);

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
            'phone' => '123456',
            'address' => 'Street 1',
            'type' => 'employee',
            'status' => 'active',
        ]);
    }
}
