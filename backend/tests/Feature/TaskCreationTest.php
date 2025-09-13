<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_task_has_status_and_slug(): void
    {
        $tenant = Tenant::create(['name' => 'T', 'features' => ['tasks']]);
        $role = Role::create([
            'name' => 'Admin',
            'slug' => 'admin',
            'tenant_id' => $tenant->id,
            'abilities' => ['tasks.manage'],
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

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/tasks', ['title' => 'A']);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.status_slug', 'draft');

        $taskId = $response->json('data.id');

        $this->assertDatabaseHas('tasks', [
            'id' => $taskId,
            'status' => 'draft',
            'status_slug' => \App\Models\TaskStatus::prefixSlug('draft', $tenant->id),
        ]);
    }
}
