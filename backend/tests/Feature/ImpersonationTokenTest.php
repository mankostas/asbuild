<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ImpersonationTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_token_with_wildcard_grants_super_admin_access(): void
    {
        $tenant = Tenant::create(['name' => 'Tenant', 'features' => ['appointments']]);

        $user = User::create([
            'name' => 'User',
            'email' => 'user@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
        ]);

        $token = $user->createToken('impersonation', ['*']);

        $this->withHeader('Authorization', 'Bearer ' . $token->plainTextToken)
            ->getJson('/api/tenants')
            ->assertStatus(200);
    }
}
