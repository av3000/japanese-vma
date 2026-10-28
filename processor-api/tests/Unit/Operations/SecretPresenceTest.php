<?php

declare(strict_types=1);

namespace Tests\Unit\Operations;

use App\Application\Operations\Support\SecretPresence;
use Tests\TestCase;

final class SecretPresenceTest extends TestCase
{
    public function test_each_secret_is_reported_as_set_or_missing_and_never_by_value(): void
    {
        $described = SecretPresence::describe([
            'APP_KEY' => 'base64:very-secret-key',
            'Sentry DSN' => null,
            'Mail password' => '',
            'Reverb secret' => '   ',
        ]);

        self::assertSame(
            ['APP_KEY: set', 'Sentry DSN: missing', 'Mail password: missing', 'Reverb secret: missing'],
            $described,
        );
        self::assertStringNotContainsString('very-secret-key', implode(' ', $described));
    }

    public function test_the_reported_secrets_come_from_config(): void
    {
        config(['app.key' => 'base64:abc', 'sentry.dsn' => null]);

        $secrets = SecretPresence::fromConfig();

        self::assertSame(['APP_KEY', 'Sentry DSN', 'Reverb secret', 'Horizon basic auth', 'Mail password'], array_keys($secrets));
        self::assertSame('base64:abc', $secrets['APP_KEY']);
    }
}
