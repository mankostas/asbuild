<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\TaskType;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskTypeBulkActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function createUserWithAbilities(array $abilities)
    {
        $tenant = Tenant::create(['name' => 'T', 'features' => ['tasks']]);
        $role = Role::create([
            'name' => 'Admin',
            'slug' => 'admin',
            'tenant_id' => $tenant->id,
            'abilities' => $abilities,
            'level' => 1,
        ]);
        $user = User::create([
            'name' => 'U',
            'email' => 'u@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $user->roles()->attach($role->id, ['tenant_id' => $tenant->id]);
        Sanctum::actingAs($user);

        return [$tenant, $user];
    }

    public function test_bulk_delete_task_types(): void
    {
        [$tenant, $user] = $this->createUserWithAbilities(['task_types.delete']);
        $type1 = TaskType::create(['name' => 'T1', 'tenant_id' => $tenant->id]);
        $type2 = TaskType::create(['name' => 'T2', 'tenant_id' => $tenant->id]);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/task-types/bulk-delete', ['ids' => [$type1->id, $type2->id]])
            ->assertOk();

        $this->assertDatabaseMissing('task_types', ['id' => $type1->id]);
        $this->assertDatabaseMissing('task_types', ['id' => $type2->id]);
    }

    public function test_tenant_admin_cannot_bulk_copy_task_types_to_other_tenant(): void
    {
        [$tenant, $user] = $this->createUserWithAbilities(['task_types.manage']);
        $targetTenant = Tenant::create(['name' => 'T2', 'features' => ['tasks']]);
        $type1 = TaskType::create(['name' => 'T1', 'tenant_id' => $tenant->id]);
        $type2 = TaskType::create(['name' => 'T2', 'tenant_id' => $tenant->id]);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/task-types/bulk-copy-to-tenant', [
                'ids' => [$type1->id, $type2->id],
                'tenant_id' => $targetTenant->id,
            ])
            ->assertStatus(403);

        $this->assertDatabaseMissing('task_types', ['name' => 'T1', 'tenant_id' => $targetTenant->id]);
        $this->assertDatabaseMissing('task_types', ['name' => 'T2', 'tenant_id' => $targetTenant->id]);
    }

    public function test_super_admin_can_bulk_copy_task_types_to_any_tenant(): void
    {
        $sourceTenant = Tenant::create(['name' => 'T1', 'features' => ['tasks']]);
        $targetTenant = Tenant::create(['name' => 'T2', 'features' => ['tasks']]);

        $role = Role::create([
            'name' => 'SuperAdmin',
            'slug' => 'super_admin',
            'tenant_id' => null,
            'abilities' => ['task_types.manage'],
            'level' => 0,
        ]);

        $user = User::create([
            'name' => 'U',
            'email' => 'super@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $sourceTenant->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $user->roles()->attach($role->id, ['tenant_id' => $sourceTenant->id]);
        Sanctum::actingAs($user);

        $type1 = TaskType::create(['name' => 'T1', 'tenant_id' => $sourceTenant->id]);
        $type2 = TaskType::create(['name' => 'T2', 'tenant_id' => $sourceTenant->id]);

        $this->withHeader('X-Tenant-ID', $sourceTenant->id)
            ->postJson('/api/task-types/bulk-copy-to-tenant', [
                'ids' => [$type1->id, $type2->id],
                'tenant_id' => $targetTenant->id,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('task_types', ['name' => 'T1', 'tenant_id' => $targetTenant->id]);
        $this->assertDatabaseHas('task_types', ['name' => 'T2', 'tenant_id' => $targetTenant->id]);
    }
}
