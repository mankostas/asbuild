<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use App\Support\PublicIdGenerator;

class ImpersonationTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_impersonation_token_cannot_access_super_admin_routes(): void
    {
        $tenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Tenant', 'features' => ['tasks']
        ]);

        $user = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'User',
            'email' => 'user@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
        ]);

        $token = $user->createToken('impersonation', ['*']);

        $this->withHeader('Authorization', 'Bearer ' . $token->plainTextToken)
            ->getJson('/api/tenants')
            ->assertStatus(403);
    }

    public function test_impersonation_token_has_tenant_access(): void
    {
        $tenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Tenant', 'features' => ['tasks']
        ]);

        $user = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'User',
            'email' => 'user@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
        ]);

        $token = $user->createToken('impersonation', ['*']);

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->plainTextToken,
            'X-Tenant-ID' => $tenant->id,
        ])->getJson('/api/lookups/features')
            ->assertStatus(200);
    }
}