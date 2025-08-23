<?php
use PHPUnit\Framework\TestCase;

class AuthTest extends TestCase
{
    public function test_user_fixture_has_email(): void
    {
        $data = json_decode(file_get_contents(__DIR__ . '/fixtures/user.json'), true);
        $this->assertEquals('jane@example.com', $data['email']);
    }

    public function test_auth_feature_placeholder(): void
    {
        $this->markTestIncomplete('Auth logic not implemented yet.');
    }
}
