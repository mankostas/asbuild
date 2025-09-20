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
use App\Support\PublicIdGenerator;

class ClientManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: Tenant, 1: User, tenant_public_id: string, user_public_id: string}
     */
    protected function createTenantUserWithAbilities(array $abilities = []): array
    {
        $tenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Tenant', 'features' => ['clients', 'tasks', 'task_types']
        ]);

        $role = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Manager',
            'slug' => 'manager',
            'tenant_id' => $tenant->id,
            'abilities' => $abilities,
            'level' => 1,
        ]);

        $user = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'User',
            'email' => 'user@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $user->roles()->attach($role->id, ['tenant_id' => $tenant->id]);

        Sanctum::actingAs($user);

        return [
            $tenant,
            $user,
            'tenant_public_id' => $this->publicIdFor($tenant),
            'user_public_id' => $this->publicIdFor($user),
        ];
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

        $clientPublicId = $response->json('data.id');
        $this->assertIsString($clientPublicId);

        $client = Client::query()->where('public_id', $clientPublicId)->first();
        $this->assertNotNull($client);
        $this->assertSame($clientPublicId, $client->public_id);

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

        $clientPublicId = $response->json('data.id');
        $this->assertIsString($clientPublicId);

        $clientId = $this->idFromPublicId(Client::class, $clientPublicId);
        $client = Client::query()->find($clientId);
        $this->assertNotNull($client);
        $this->assertSame($clientId, $client->getKey());
        $this->assertSame($clientPublicId, $client->public_id);

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
            ->assertJsonPath('data.0.name', 'Acme Corp')
            ->assertJsonPath('data.0.id', $clientPublicId);

        $archive = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson("/api/clients/{$clientPublicId}/archive")
            ->assertOk();

        $this->assertNotNull($archive->json('data.archived_at'));

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->getJson('/api/clients')
            ->assertOk()
            ->assertJsonMissing(['id' => $clientPublicId]);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->getJson('/api/clients?archived=only')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $clientPublicId);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->deleteJson("/api/clients/{$clientPublicId}/archive")
            ->assertOk()
            ->assertJsonPath('data.archived_at', null);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->deleteJson("/api/clients/{$clientPublicId}")
            ->assertOk();

        $this->assertSoftDeleted('clients', ['id' => $clientId]);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson("/api/clients/{$clientPublicId}/restore")
            ->assertOk();

        $this->assertDatabaseHas('clients', ['id' => $clientId, 'deleted_at' => null, 'user_id' => null]);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->getJson("/api/clients/{$clientPublicId}")
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

        $tenantB = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Tenant B', 'features' => ['clients', 'tasks', 'task_types']
        ]);
        $foreignClient = Client::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => $tenantB->id,
            'name' => 'Foreign',
            'email' => 'foreign@example.com',
        ]);

        $this->withHeader('X-Tenant-ID', $tenantA->id)
            ->postJson('/api/task-types', [
                'name' => 'Type With Client',
                'client_id' => $foreignClient->public_id,
            ])
            ->assertStatus(422);

        $this->withHeader('X-Tenant-ID', $tenantA->id)
            ->postJson('/api/tasks', [
                'task_type_id' => null,
                'client_id' => $foreignClient->public_id,
            ])
            ->assertStatus(422);
    }

    public function test_super_admin_can_list_clients_for_specific_tenant(): void
    {
        $tenantA = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Tenant A', 'features' => ['clients', 'tasks', 'task_types']
        ]);
        $tenantB = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Tenant B', 'features' => ['clients', 'tasks', 'task_types']
        ]);

        $clientA = Client::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => $tenantA->id,
            'name' => 'Tenant A Client',
            'email' => 'a@example.com',
        ]);

        $clientB1 = Client::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => $tenantB->id,
            'name' => 'Tenant B One',
            'email' => 'b1@example.com',
        ]);

        $clientB2 = Client::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => $tenantB->id,
            'name' => 'Tenant B Two',
            'email' => 'b2@example.com',
        ]);

        $role = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'SuperAdmin',
            'slug' => 'super_admin',
            'tenant_id' => $tenantA->id,
            'abilities' => ['*'],
            'level' => 0,
        ]);

        $super = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Super',
            'email' => 'super@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenantA->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $super->roles()->attach($role->id, ['tenant_id' => $tenantA->id]);

        Sanctum::actingAs($super);

        $response = $this->getJson('/api/clients?tenant_id=' . $this->publicIdFor($tenantB))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2)
            ->json('data');

        $this->assertEqualsCanonicalizing([
            $this->publicIdFor($clientB1),
            $this->publicIdFor($clientB2),
        ], collect($response)->pluck('id')->all());

        $this->assertTrue(
            collect($response)->every(fn ($client) => $client['tenant_id'] === $this->publicIdFor($tenantB))
        );

        $this->assertNotContains($this->publicIdFor($clientA), collect($response)->pluck('id')->all());
    }

    public function test_tenant_user_cannot_override_tenant_scope_or_view_foreign_clients(): void
    {
        [$tenantA, $user] = $this->createTenantUserWithAbilities([
            'clients.view',
            'clients.manage',
        ]);

        $tenantB = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Tenant B', 'features' => ['clients', 'tasks', 'task_types']
        ]);
        $clientB = Client::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => $tenantB->id,
            'name' => 'Tenant B Client',
            'email' => 'tenant-b@example.com',
        ]);

        $this->withHeader('X-Tenant-ID', $tenantB->id)
            ->getJson('/api/clients')
            ->assertForbidden();

        $this->withHeader('X-Tenant-ID', $tenantA->id)
            ->getJson("/api/clients/{$clientB->public_id}")
            ->assertForbidden();
    }

    public function test_super_admin_can_target_any_tenant_client(): void
    {
        $tenantA = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'A', 'features' => ['clients', 'task_types', 'tasks']
        ]);
        $tenantB = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'B', 'features' => ['clients', 'task_types', 'tasks']
        ]);

        $clientB = Client::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => $tenantB->id,
            'name' => 'Target',
            'email' => 'target@example.com',
        ]);

        $role = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'SuperAdmin',
            'slug' => 'super_admin',
            'tenant_id' => $tenantA->id,
            'abilities' => ['*'],
            'level' => 0,
        ]);

        $super = User::create([
            'public_id' => PublicIdGenerator::generate(),
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
                'client_id' => $clientB->public_id,
                'tenant_id' => $tenantB->public_id,
            ])
            ->assertCreated()
            ->json('data.id');

        $task = $this->withHeader('X-Tenant-ID', $tenantB->id)
            ->postJson('/api/tasks', [
                'task_type_id' => $taskType,
            ])
            ->assertCreated()
            ->json('data');

        $this->assertEquals($clientB->public_id, $task['client']['id']);
    }

    public function test_tenant_manager_can_bulk_archive_clients(): void
    {
        [$tenant] = $this->createTenantUserWithAbilities([
            'clients.view',
            'clients.update',
            'clients.manage',
        ]);

        $clients = collect(range(1, 3))->map(function (int $i) use ($tenant) {
            return Client::create([
                'public_id' => PublicIdGenerator::generate(),
                'tenant_id' => $tenant->id,
                'name' => "Client {$i}",
                'email' => "client{$i}@example.com",
            ]);
        });

        $ids = $clients->pluck('id')->all();

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/clients/bulk-archive', ['ids' => $ids])
            ->assertOk();

        $response->assertJsonCount(3, 'data');

        foreach ($clients as $client) {
            $this->assertNotNull($client->fresh()->archived_at);
        }
    }

    public function test_bulk_archive_rejects_foreign_clients(): void
    {
        [$tenant] = $this->createTenantUserWithAbilities([
            'clients.view',
            'clients.update',
            'clients.manage',
        ]);

        $tenantB = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Tenant B', 'features' => ['clients', 'tasks', 'task_types']
        ]);

        $ownClient = Client::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => $tenant->id,
            'name' => 'Local',
            'email' => 'local@example.com',
        ]);

        $foreignClient = Client::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => $tenantB->id,
            'name' => 'Foreign',
            'email' => 'foreign@example.com',
        ]);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/clients/bulk-archive', ['ids' => [$ownClient->id, $foreignClient->id]])
            ->assertForbidden();

        $this->assertNull($ownClient->fresh()->archived_at);
        $this->assertNull($foreignClient->fresh()->archived_at);
    }

    public function test_tenant_manager_can_bulk_delete_clients(): void
    {
        [$tenant] = $this->createTenantUserWithAbilities([
            'clients.view',
            'clients.delete',
            'clients.manage',
        ]);

        $clients = collect(range(1, 2))->map(function (int $i) use ($tenant) {
            return Client::create([
                'public_id' => PublicIdGenerator::generate(),
                'tenant_id' => $tenant->id,
                'name' => "Client {$i}",
                'email' => "client{$i}@example.com",
            ]);
        });

        $ids = $clients->pluck('id')->all();

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/clients/bulk-delete', ['ids' => $ids])
            ->assertOk()
            ->assertJson(['message' => 'deleted']);

        foreach ($clients as $client) {
            $this->assertSoftDeleted('clients', ['id' => $client->id]);
        }
    }

    public function test_bulk_delete_rejects_foreign_clients(): void
    {
        [$tenant] = $this->createTenantUserWithAbilities([
            'clients.view',
            'clients.delete',
            'clients.manage',
        ]);

        $tenantB = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Tenant B', 'features' => ['clients', 'tasks', 'task_types']
        ]);

        $ownClient = Client::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => $tenant->id,
            'name' => 'Local',
            'email' => 'local@example.com',
        ]);

        $foreignClient = Client::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => $tenantB->id,
            'name' => 'Foreign',
            'email' => 'foreign@example.com',
        ]);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/clients/bulk-delete', ['ids' => [$ownClient->id, $foreignClient->id]])
            ->assertForbidden();

        $this->assertDatabaseHas('clients', ['id' => $ownClient->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('clients', ['id' => $foreignClient->id, 'deleted_at' => null]);
    }
}
