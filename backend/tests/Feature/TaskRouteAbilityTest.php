<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use App\Support\PublicIdGenerator;

class TaskRouteAbilityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @dataProvider abilityProvider
     */
    public function test_task_routes_require_abilities(string $method, callable $resolver, string $ability): void
    {
        $tenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'T', 'features' => ['tasks']
        ]);
        $role = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'User', 'slug' => 'user', 'tenant_id' => $tenant->id, 'abilities' => [], 'level' => 1
        ]);
        $user = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'U',
            'email' => 'u@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $user->roles()->attach($role->id, ['tenant_id' => $tenant->id]);
        $task = Task::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => $tenant->id, 'user_id' => $user->id
        ]);
        Sanctum::actingAs($user);

        [$url, $payload] = $resolver($task, $user);
        $this->withHeader('X-Tenant-ID', $tenant->public_id)
            ->json($method, $url, $payload)
            ->assertStatus(403);

        $abilityRole = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Ability', 'slug' => 'ability', 'tenant_id' => $tenant->id, 'abilities' => [$ability, 'tasks.manage'], 'level' => 1
        ]);
        $user->roles()->attach($abilityRole->id, ['tenant_id' => $tenant->id]);
        $user->refresh();

        $expected = $method === 'POST' ? 201 : 200;
        $this->withHeader('X-Tenant-ID', $tenant->public_id)
            ->json($method, $url, $payload)
            ->assertStatus($expected);

        \App\Models\Tenant::setCurrent(null);
        config()->set('tenant', []);
    }

    public static function abilityProvider(): array
    {
        return [
            'index' => ['GET', fn($task, $user) => ['/api/tasks', []], 'tasks.view'],
            'show' => ['GET', fn($task, $user) => ["/api/tasks/{$task->public_id}", []], 'tasks.view'],
        ];
    }

    public function test_tenant_isolation_on_tasks(): void
    {
        $tenant1 = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'One', 'features' => ['tasks']
        ]);
        $tenant2 = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Two', 'features' => ['tasks']
        ]);

        $role = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'User', 'slug' => 'user', 'tenant_id' => $tenant1->id, 'abilities' => ['tasks.view', 'tasks.manage'], 'level' => 1
        ]);
        $user = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'U',
            'email' => 'u@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant1->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $user->roles()->attach($role->id, ['tenant_id' => $tenant1->id]);
        Sanctum::actingAs($user);

        $task1 = Task::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => $tenant1->id, 'user_id' => $user->id
        ]);
        $task2 = Task::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => $tenant2->id, 'user_id' => $user->id
        ]);

        $this->withHeader('X-Tenant-ID', $tenant1->public_id)
            ->getJson('/api/tasks')
            ->assertJsonCount(1, 'data');

        $this->withHeader('X-Tenant-ID', $tenant1->public_id)
            ->getJson("/api/tasks/{$task2->public_id}")
            ->assertStatus(403);

        \App\Models\Tenant::setCurrent(null);
        config()->set('tenant', []);
    }
}
