<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskStatusCreateAbilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_create_status_without_ability(): void
    {
        $tenant = Tenant::create(['name' => 'Tenant', 'features' => ['task_statuses']]);
        $role = Role::create([
            'name' => 'User',
            'slug' => 'user',
            'tenant_id' => $tenant->id,
            'abilities' => [],
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

        $payload = ['name' => 'Test', 'slug' => 'test'];
        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/task-statuses', $payload)
            ->assertStatus(403);
    }

    public function test_can_create_status_with_ability(): void
    {
        $tenant = Tenant::create(['name' => 'Tenant', 'features' => ['task_statuses']]);
        $role = Role::create([
            'name' => 'Manager',
            'slug' => 'manager',
            'tenant_id' => $tenant->id,
            'abilities' => ['task_statuses.manage'],
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

        $payload = ['name' => 'Test2', 'slug' => 'test2'];
        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/task-statuses', $payload)
            ->assertStatus(201);
    }
}
