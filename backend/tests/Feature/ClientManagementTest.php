<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ClientManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function createTenantUserWithAbilities(array $abilities = []): array
    {
        $tenant = Tenant::create(['name' => 'Tenant', 'features' => ['clients', 'tasks', 'task_types']]);

        $role = Role::create([
            'name' => 'Manager',
            'slug' => 'manager',
            'tenant_id' => $tenant->id,
            'abilities' => $abilities,
            'level' => 1,
        ]);

        $user = User::create([
            'name' => 'User',
            'email' => 'user@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $user->roles()->attach($role->id, ['tenant_id' => $tenant->id]);

        Sanctum::actingAs($user);

        return [$tenant, $user];
    }

    public function test_tenant_manager_can_perform_full_client_lifecycle(): void
    {
        [$tenant, $user] = $this->createTenantUserWithAbilities([
            'clients.view',
            'clients.create',
            'clients.update',
            'clients.delete',
            'clients.manage',
        ]);

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/clients', [
                'name' => 'Acme Corp',
                'email' => 'contact@acme.test',
                'phone' => '555-1234',
                'notes' => 'Important customer',
            ])
            ->assertCreated();

        $clientId = $response->json('data.id');
        $this->assertDatabaseHas('clients', [
            'id' => $clientId,
            'tenant_id' => $tenant->id,
            'deleted_at' => null,
            'archived_at' => null,
        ]);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->getJson('/api/clients')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.name', 'Acme Corp');

        $archive = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson("/api/clients/{$clientId}/archive")
            ->assertOk();

        $this->assertNotNull($archive->json('data.archived_at'));

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->getJson('/api/clients')
            ->assertOk()
            ->assertJsonMissing(['id' => $clientId]);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->getJson('/api/clients?archived=only')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $clientId);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->deleteJson("/api/clients/{$clientId}/archive")
            ->assertOk()
            ->assertJsonPath('data.archived_at', null);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->deleteJson("/api/clients/{$clientId}")
            ->assertOk();

        $this->assertSoftDeleted('clients', ['id' => $clientId]);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson("/api/clients/{$clientId}/restore")
            ->assertOk();

        $this->assertDatabaseHas('clients', ['id' => $clientId, 'deleted_at' => null, 'user_id' => null]);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->getJson("/api/clients/{$clientId}")
            ->assertOk()
            ->assertJsonMissingPath('data.owner');
    }

    public function test_tenant_cannot_use_foreign_client_for_task_type_or_task(): void
    {
        [$tenantA, $user] = $this->createTenantUserWithAbilities([
            'task_types.create',
            'tasks.create',
            'tasks.manage',
            'clients.view',
        ]);

        $tenantB = Tenant::create(['name' => 'Tenant B', 'features' => ['clients', 'tasks', 'task_types']]);
        $foreignClient = Client::create([
            'tenant_id' => $tenantB->id,
            'name' => 'Foreign',
            'email' => 'foreign@example.com',
        ]);

        $this->withHeader('X-Tenant-ID', $tenantA->id)
            ->postJson('/api/task-types', [
                'name' => 'Type With Client',
                'client_id' => $foreignClient->id,
            ])
            ->assertStatus(422);

        $this->withHeader('X-Tenant-ID', $tenantA->id)
            ->postJson('/api/tasks', [
                'task_type_id' => null,
                'client_id' => $foreignClient->id,
            ])
            ->assertStatus(422);
    }

    public function test_super_admin_can_target_any_tenant_client(): void
    {
        $tenantA = Tenant::create(['name' => 'A', 'features' => ['clients', 'task_types', 'tasks']]);
        $tenantB = Tenant::create(['name' => 'B', 'features' => ['clients', 'task_types', 'tasks']]);

        $clientB = Client::create([
            'tenant_id' => $tenantB->id,
            'name' => 'Target',
            'email' => 'target@example.com',
        ]);

        $role = Role::create([
            'name' => 'SuperAdmin',
            'slug' => 'super_admin',
            'tenant_id' => $tenantA->id,
            'abilities' => ['*'],
            'level' => 0,
        ]);

        $super = User::create([
            'name' => 'Super',
            'email' => 'super@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenantA->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $super->roles()->attach($role->id, ['tenant_id' => $tenantA->id]);

        Sanctum::actingAs($super);

        $taskType = $this->withHeader('X-Tenant-ID', $tenantB->id)
            ->postJson('/api/task-types', [
                'name' => 'SA Type',
                'client_id' => $clientB->id,
                'tenant_id' => $tenantB->id,
            ])
            ->assertCreated()
            ->json('data.id');

        $task = $this->withHeader('X-Tenant-ID', $tenantB->id)
            ->postJson('/api/tasks', [
                'task_type_id' => $taskType,
            ])
            ->assertCreated()
            ->json('data');

        $this->assertEquals($clientB->id, $task['client']['id']);
    }
}
