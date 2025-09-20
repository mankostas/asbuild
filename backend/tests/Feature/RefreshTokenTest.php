<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use App\Support\PublicIdGenerator;

class RefreshTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_refresh_token_can_be_renewed(): void
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

        $refresh = $user->createToken('refresh-token', ['refresh'], now()->addDays(30));

        $response = $this->postJson('/api/auth/refresh', [
            'refresh_token' => $refresh->plainTextToken,
        ]);

        $response->assertStatus(200)->assertJsonStructure(['access_token', 'refresh_token']);

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $refresh->accessToken->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'access-token', 'tokenable_id' => $user->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'refresh-token', 'tokenable_id' => $user->id]);
    }

    public function test_impersonation_refresh_token_can_be_renewed(): void
    {
        $tenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Tenant', 'features' => ['tasks']
        ]);

        $user = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'User',
            'email' => 'user2@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
        ]);

        $refresh = $user->createToken('impersonation-refresh', ['refresh'], now()->addDays(30));

        $response = $this->postJson('/api/auth/refresh', [
            'refresh_token' => $refresh->plainTextToken,
        ]);

        $response->assertStatus(200)->assertJsonStructure(['access_token', 'refresh_token']);

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $refresh->accessToken->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'impersonation', 'tokenable_id' => $user->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'impersonation-refresh', 'tokenable_id' => $user->id]);
    }
}
