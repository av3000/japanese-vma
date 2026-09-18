<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Guards the transaction-safety setting introduced for issue #244. Every queue connection the
 * app actually runs on must defer dispatch until the surrounding transaction commits.
 */
class QueueAfterCommitConfigTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function connections(): array
    {
        return [
            'redis (production worker)' => ['redis'],
            'database' => ['database'],
            'sync (web runtime, CI smoke, tests)' => ['sync'],
        ];
    }

    #[DataProvider('connections')]
    public function test_connection_dispatches_after_commit(string $connection): void
    {
        $this->assertTrue(
            config("queue.connections.{$connection}.after_commit"),
            "queue.connections.{$connection}.after_commit must be true",
        );
    }
}
