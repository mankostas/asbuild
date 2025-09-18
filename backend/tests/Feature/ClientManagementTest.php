<?php

namespace Tests\Feature;

use App\Mail\ClientWelcomeMail;
use App\Models\Client;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
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

    public function test_client_creation_can_send_welcome_email(): void
    {
        Mail::fake();

        [$tenant] = $this->createTenantUserWithAbilities([
            'clients.create',
            'clients.manage',
        ]);

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/clients', [
                'name' => 'Acme Corp',
                'email' => 'welcome@example.com',
                'notify_client' => true,
            ])
            ->assertCreated();

        $clientId = $response->json('data.id');
        $this->assertNotNull($clientId);

        Mail::assertSent(ClientWelcomeMail::class, function (ClientWelcomeMail $mail) {
            return $mail->hasTo('welcome@example.com');
        });
    }

    public function test_client_creation_without_notify_flag_does_not_send_email(): void
    {
        Mail::fake();

        [$tenant] = $this->createTenantUserWithAbilities([
            'clients.create',
            'clients.manage',
        ]);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/clients', [
                'name' => 'Silent Corp',
                'email' => 'silent@example.com',
            ])
            ->assertCreated();

        Mail::assertNothingSent();
    }

    public function test_client_creation_requires_email_when_notifying(): void
    {
        Mail::fake();

        [$tenant] = $this->createTenantUserWithAbilities([
            'clients.create',
            'clients.manage',
        ]);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/clients', [
                'name' => 'Acme Corp',
                'notify_client' => true,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        Mail::assertNothingSent();
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

    public function test_super_admin_can_list_clients_for_specific_tenant(): void
    {
        $tenantA = Tenant::create(['name' => 'Tenant A', 'features' => ['clients', 'tasks', 'task_types']]);
        $tenantB = Tenant::create(['name' => 'Tenant B', 'features' => ['clients', 'tasks', 'task_types']]);

        $clientA = Client::create([
            'tenant_id' => $tenantA->id,
            'name' => 'Tenant A Client',
            'email' => 'a@example.com',
        ]);

        $clientB1 = Client::create([
            'tenant_id' => $tenantB->id,
            'name' => 'Tenant B One',
            'email' => 'b1@example.com',
        ]);

        $clientB2 = Client::create([
            'tenant_id' => $tenantB->id,
            'name' => 'Tenant B Two',
            'email' => 'b2@example.com',
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

        $response = $this->getJson('/api/clients?tenant_id=' . $tenantB->id)
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2)
            ->json('data');

        $this->assertEqualsCanonicalizing([
            $clientB1->id,
            $clientB2->id,
        ], collect($response)->pluck('id')->all());

        $this->assertTrue(
            collect($response)->every(fn ($client) => (int) $client['tenant_id'] === $tenantB->id)
        );

        $this->assertNotContains($clientA->id, collect($response)->pluck('id')->all());
    }

    public function test_tenant_user_cannot_override_tenant_scope_or_view_foreign_clients(): void
    {
        [$tenantA, $user] = $this->createTenantUserWithAbilities([
            'clients.view',
            'clients.manage',
        ]);

        $tenantB = Tenant::create(['name' => 'Tenant B', 'features' => ['clients', 'tasks', 'task_types']]);
        $clientB = Client::create([
            'tenant_id' => $tenantB->id,
            'name' => 'Tenant B Client',
            'email' => 'tenant-b@example.com',
        ]);

        $this->withHeader('X-Tenant-ID', $tenantB->id)
            ->getJson('/api/clients')
            ->assertForbidden();

        $this->withHeader('X-Tenant-ID', $tenantA->id)
            ->getJson("/api/clients/{$clientB->id}")
            ->assertForbidden();
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
