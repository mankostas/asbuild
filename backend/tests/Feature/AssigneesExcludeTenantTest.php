<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use App\Support\PublicIdGenerator;

class AssigneesExcludeTenantTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_user_not_returned_in_assignees(): void
    {
        $tenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'id' => 1, 'name' => 'T', 'features' => ['tasks']
        ]);
        $tenantRole = $tenant->roles()->where('slug', 'tenant')->first();

        $tenantUser = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'TenantUser',
            'email' => 'tenant@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '1',
            'address' => 'A',
        ]);
        $tenantUser->roles()->attach($tenantRole->id, ['tenant_id' => $tenant->id]);

        $role = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Agent',
            'slug' => 'agent',
            'tenant_id' => $tenant->id,
            'abilities' => [],
            'level' => 2,
        ]);
        $employee = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Employee',
            'email' => 'emp@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '1',
            'address' => 'A',
        ]);
        $employee->roles()->attach($role->id, ['tenant_id' => $tenant->id]);
        Sanctum::actingAs($employee);

        $response = $this->withHeader('X-Tenant-ID', $this->publicIdFor($tenant))
            ->getJson('/api/lookups/assignees?type=employees');

        $response->assertStatus(200);
        $labels = collect($response->json())->pluck('label')->all();
        $this->assertEquals(['Employee'], $labels);
    }
}
