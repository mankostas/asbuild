<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\TaskStatus;
use App\Models\Tenant;
use App\Models\User;
use App\Support\PublicIdGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskStatusCopyTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_copy_to_tenant_regenerates_public_id(): void
    {
        $sourceTenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Source',
            'features' => ['tasks'],
        ]);

        $targetTenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Target',
            'features' => ['tasks'],
        ]);

        $superTenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Root',
        ]);

        $superRole = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'SuperAdmin',
            'slug' => 'super_admin',
            'tenant_id' => $superTenant->id,
            'abilities' => ['*'],
            'level' => 0,
        ]);

        $user = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Super User',
            'email' => 'super@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $superTenant->id,
            'phone' => '1234567890',
            'address' => '123 Admin Way',
        ]);
        $user->roles()->attach($superRole->id, ['tenant_id' => $superTenant->id]);

        Sanctum::actingAs($user);

        $status = TaskStatus::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'In Progress',
            'tenant_id' => $sourceTenant->id,
            'color' => '#abcdef',
        ]);

        $originalPublicId = $this->publicIdFor($status);

        $response = $this->withHeader('X-Tenant-ID', (string) $sourceTenant->id)
            ->postJson(
                "/api/task-statuses/{$originalPublicId}/copy-to-tenant",
                ['tenant_id' => $this->publicIdFor($targetTenant)]
            );

        $response->assertCreated();
        $response->assertJsonPath('data.name', 'In Progress');
        $response->assertJsonPath('data.tenant_id', $this->publicIdFor($targetTenant));

        $newPublicId = $response->json('data.id');

        $this->assertIsString($newPublicId);
        $this->assertNotSame($originalPublicId, $newPublicId);

        $this->assertDatabaseHas('task_statuses', [
            'id' => $this->idFromPublicId(TaskStatus::class, $newPublicId),
            'tenant_id' => $targetTenant->id,
            'name' => 'In Progress',
        ]);

        $this->assertDatabaseHas('task_statuses', [
            'id' => $status->id,
            'public_id' => $originalPublicId,
        ]);
    }
}

