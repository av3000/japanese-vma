<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * `study_mcp.enabled` decides whether `/mcp/study` exists. The default must keep production off
 * until a maintainer turns it on, so each case evaluates the config file under a chosen environment.
 */
final class StudyMcpConfigTest extends TestCase
{
    private const VARIABLES = ['APP_ENV', 'STUDY_MCP_ENABLED'];

    /** @var array<string, string|false> */
    private array $original = [];

    protected function setUp(): void
    {
        parent::setUp();

        foreach (self::VARIABLES as $name) {
            $this->original[$name] = getenv($name);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->original as $name => $value) {
            $this->setEnvironment($name, $value === false ? null : $value);
        }

        parent::tearDown();
    }

    /**
     * @return array<string, array{string, ?string, bool}>
     */
    public static function environments(): array
    {
        return [
            'unset on local' => ['local', null, true],
            'unset on testing' => ['testing', null, true],
            'unset on staging' => ['staging', null, true],
            'unset on production' => ['production', null, false],
            'true on production' => ['production', 'true', true],
            'false on local' => ['local', 'false', false],
            'false on production' => ['production', 'false', false],
        ];
    }

    #[DataProvider('environments')]
    public function test_enabled_follows_the_environment_unless_the_variable_is_set(
        string $appEnv,
        ?string $variable,
        bool $expected,
    ): void {
        $this->setEnvironment('APP_ENV', $appEnv);
        $this->setEnvironment('STUDY_MCP_ENABLED', $variable);

        $config = require config_path('study_mcp.php');

        $this->assertSame($expected, $config['enabled']);
    }

    private function setEnvironment(string $name, ?string $value): void
    {
        if ($value === null) {
            putenv($name);
            unset($_ENV[$name], $_SERVER[$name]);

            return;
        }

        putenv("{$name}={$value}");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}
