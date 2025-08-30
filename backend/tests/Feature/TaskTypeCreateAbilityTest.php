<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskTypeCreateAbilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_create_task_type_without_create_ability(): void
    {
        $tenant = Tenant::create(['name' => 'Tenant', 'features' => ['task_types']]);
        $role = Role::create([
            'name' => 'ClientAdmin',
            'slug' => 'client_admin',
            'tenant_id' => $tenant->id,
            'abilities' => ['task_types.view'],
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

        $payload = [
            'name' => 'Type',
            'schema_json' => json_encode(['sections' => []]),
            'statuses' => json_encode([]),
        ];

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/task-types', $payload)
            ->assertStatus(403);
    }

    public function test_can_create_task_type_with_create_ability(): void
    {
        $tenant = Tenant::create(['name' => 'Tenant', 'features' => ['task_types']]);
        $role = Role::create([
            'name' => 'ClientAdmin',
            'slug' => 'client_admin',
            'tenant_id' => $tenant->id,
            'abilities' => ['task_types.create'],
            'level' => 1,
        ]);
        $user = User::create([
            'name' => 'User',
            'email' => 'user2@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $user->roles()->attach($role->id, ['tenant_id' => $tenant->id]);
        Sanctum::actingAs($user);

        $payload = [
            'name' => 'Type2',
            'schema_json' => json_encode(['sections' => []]),
            'statuses' => json_encode([]),
        ];

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/task-types', $payload)
            ->assertStatus(201);
    }
}
