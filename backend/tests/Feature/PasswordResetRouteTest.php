<?php

namespace Tests\Feature;

use Tests\TestCase;

class PasswordResetRouteTest extends TestCase
{
    public function test_password_reset_route_redirects_to_frontend(): void
    {
        $response = $this->get('/api/auth/password/reset/test-token?email=user@example.com');

        $response->assertRedirect('http://localhost:5173/reset-password?token=test-token&email=user%40example.com');
    }
}
