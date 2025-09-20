<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Models\TaskType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use App\Support\PublicIdGenerator;

class TaskTypeAbilityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @dataProvider abilityProvider
     */
    public function test_routes_require_abilities(string $method, callable $resolver, string $ability): void
    {
        $tenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'T', 'features' => ['task_types']
        ]);
        $role = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'User',
            'slug' => 'user',
            'tenant_id' => $tenant->id,
            'abilities' => [],
            'level' => 1,
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
        Sanctum::actingAs($user);

        $type = TaskType::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Type',
            'tenant_id' => $tenant->id,
            'schema_json' => ['sections' => []],
            'statuses' => ['draft' => []],
        ]);

        [$url, $payload] = $resolver($type);
        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->json($method, $url, $payload)
            ->assertStatus(403);

        $abilityRole = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Ability',
            'slug' => 'ability',
            'tenant_id' => $tenant->id,
            'abilities' => [$ability],
            'level' => 1,
        ]);
        $user->roles()->attach($abilityRole->id, ['tenant_id' => $tenant->id]);
        $user->refresh();

        $expected = $method === 'POST' ? 201 : 200;
        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->json($method, $url, $payload)
            ->assertStatus($expected);
    }

    public static function abilityProvider(): array
    {
        return [
            'automations store' => [
                'POST',
                fn($type) => ["/api/task-types/{$type->id}/automations", [
                    'event' => 'status_changed',
                    'conditions_json' => null,
                    'actions_json' => [['type' => 'noop']],
                    'enabled' => true,
                ]],
                'task_automations.manage'
            ],
        ];
    }
}

