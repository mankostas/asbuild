<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthMeClientAbilitiesTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_scoped_abilities_are_partitioned(): void
    {
        $tenant = Tenant::create([
            'name' => 'Client Tenant',
            'features' => ['dashboard', 'tasks', 'reports'],
            'quota_storage_mb' => 0,
            'phone' => '123-456-7890',
            'address' => '123 Main St',
        ]);

        $user = User::create([
            'name' => 'Client User',
            'email' => 'client@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '123456789',
            'address' => '123 Main St',
        ]);

        $role = Role::where('tenant_id', $tenant->id)
            ->where('slug', 'client_contributor')
            ->firstOrFail();

        $role->users()->attach($user->id, ['tenant_id' => $tenant->id]);

        Sanctum::actingAs($user);

        $data = $this->getJson('/api/me')->assertOk()->json();

        $this->assertSame([], $data['abilities']);
        $this->assertEqualsCanonicalizing([
            'dashboard.client.view',
            'tasks.client.view',
            'tasks.client.create',
            'tasks.client.update',
            'reports.client.view',
        ], $data['client_abilities']);
    }
}
