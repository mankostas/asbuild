<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\TaskStatus;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskStatusSlugTest extends TestCase
{
    use RefreshDatabase;

    public function test_slug_is_generated_from_name(): void
    {
        $tenant = Tenant::create(['name' => 'T', 'features' => ['tasks']]);
        $role = Role::create([
            'name' => 'ClientAdmin',
            'slug' => 'client_admin',
            'tenant_id' => $tenant->id,
            'abilities' => ['task_statuses.manage'],
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

        $payload = ['name' => 'In Progress'];

        $statusPublicId = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/task-statuses', $payload)
            ->assertStatus(201)
            ->assertJsonPath('data.slug', 'in_progress')
            ->json('data.id');

        $this->assertIsString($statusPublicId);

        $statusId = $this->idFromPublicId(TaskStatus::class, $statusPublicId);
        $status = TaskStatus::query()->find($statusId);
        $this->assertNotNull($status);
        $this->assertSame($statusId, $status->getKey());
        $this->assertSame($statusPublicId, $status->public_id);

        $this->assertDatabaseHas('task_statuses', [
            'id' => $statusId,
            'name' => 'In Progress',
            'slug' => 'in_progress',
            'tenant_id' => $tenant->id,
        ]);
    }
}
