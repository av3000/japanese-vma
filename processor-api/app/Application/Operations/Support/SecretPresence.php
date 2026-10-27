<?php

declare(strict_types=1);

namespace App\Application\Operations\Support;

/**
 * Turns secrets into "Name: set" or "Name: missing". The values themselves never leave here, so
 * nothing on the environment page can show one.
 */
final class SecretPresence
{
    /**
     * The secrets the environment page reports, by display name.
     *
     * @return array<string, mixed>
     */
    public static function fromConfig(): array
    {
        return [
            'APP_KEY' => config('app.key'),
            'Sentry DSN' => config('sentry.dsn'),
            'Reverb secret' => config('broadcasting.connections.reverb.secret'),
            'Horizon basic auth' => config('horizon.basic_auth.password'),
            'Mail password' => config('mail.mailers.smtp.password'),
        ];
    }

    /**
     * @param array<string, mixed> $secrets display name => value
     *
     * @return list<string>
     */
    public static function describe(array $secrets): array
    {
        return array_map(
            static fn (string $name, mixed $value): string => $name.': '.(filled($value) ? 'set' : 'missing'),
            array_keys($secrets),
            array_values($secrets),
        );
    }
}
