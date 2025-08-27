<?php

use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_options_request_returns_cors_headers(): void
    {
        $response = $this->options('/api/health');

        $response->assertStatus(204);
        $response->assertHeader('Access-Control-Allow-Origin', config('security.cors.allowed_origins')[0]);
        $response->assertHeader('Access-Control-Allow-Credentials', 'true');
        $response->assertHeader('Access-Control-Allow-Headers', implode(',', config('security.cors.allowed_headers')));
    }
}
