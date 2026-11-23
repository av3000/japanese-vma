<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class AuthUserResourceOpenApiTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function apiJson(): array
    {
        return json_decode(
            file_get_contents(__DIR__.'/../../api.json'),
            true,
            flags: JSON_THROW_ON_ERROR
        );
    }

    public function test_auth_user_resource_always_carries_the_identity_fields(): void
    {
        $schema = $this->apiJson()['components']['schemas']['AuthUserResource'];

        foreach (['id', 'uuid', 'name', 'email', 'roles', 'is_admin', 'created_at'] as $field) {
            $this->assertArrayHasKey($field, $schema['properties']);
            $this->assertContains($field, $schema['required']);
        }
    }

    public function test_auth_user_resource_token_pair_is_optional(): void
    {
        $schema = $this->apiJson()['components']['schemas']['AuthUserResource'];

        // `me` returns this resource without a token, so a generated client that types the pair as
        // guaranteed would be lying about every restored session.
        $this->assertArrayHasKey('access_token', $schema['properties']);
        $this->assertArrayHasKey('token_type', $schema['properties']);
        $this->assertNotContains('access_token', $schema['required']);
        $this->assertNotContains('token_type', $schema['required']);
    }

    public function test_auth_endpoints_are_documented(): void
    {
        $paths = $this->apiJson()['paths'];

        $this->assertSame('auth.login', $paths['/login']['post']['operationId']);
        $this->assertSame('auth.register', $paths['/register']['post']['operationId']);
        $this->assertSame('auth.logout', $paths['/logout']['post']['operationId']);
        $this->assertSame('auth.me', $paths['/me']['get']['operationId']);
    }
}
