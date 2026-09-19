<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Issue #254: the Reverb server options and the broadcasting client options must derive
 * `useTLS` from the same REVERB_SCHEME default, or an unset variable makes them disagree.
 */
class ReverbTlsConfigTest extends TestCase
{
    /**
     * @return array<string, array{string|null, bool}>
     */
    public static function schemes(): array
    {
        return [
            'unset falls back to http' => [null, false],
            'http' => ['http', false],
            'https' => ['https', true],
        ];
    }

    #[DataProvider('schemes')]
    public function test_reverb_and_broadcasting_agree_on_use_tls(?string $scheme, bool $expected): void
    {
        $this->withEnv('REVERB_SCHEME', $scheme);

        $reverb = require base_path('config/reverb.php');
        $broadcasting = require base_path('config/broadcasting.php');

        $this->assertSame($expected, $reverb['apps']['apps'][0]['options']['useTLS']);
        $this->assertSame($expected, $broadcasting['connections']['reverb']['options']['useTLS']);
    }

    public function test_allowed_origins_are_read_from_the_environment(): void
    {
        $this->withEnv('REVERB_ALLOWED_ORIGINS', 'https://app.example.com, https://staging.example.com');
        $this->assertSame(
            ['https://app.example.com', 'https://staging.example.com'],
            (require base_path('config/reverb.php'))['apps']['apps'][0]['allowed_origins'],
        );

        $this->withEnv('REVERB_ALLOWED_ORIGINS', null);
        $this->assertSame(['*'], (require base_path('config/reverb.php'))['apps']['apps'][0]['allowed_origins']);
    }

    protected function tearDown(): void
    {
        $this->withEnv('REVERB_SCHEME', null);
        $this->withEnv('REVERB_ALLOWED_ORIGINS', null);

        parent::tearDown();
    }

    private function withEnv(string $key, ?string $value): void
    {
        if ($value === null) {
            putenv($key);
            unset($_ENV[$key], $_SERVER[$key]);

            return;
        }

        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}
