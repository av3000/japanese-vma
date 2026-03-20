<?php

namespace Tests\Feature\Console;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class VerifyQueueWorkerCommandTest extends TestCase
{
    public function testVerifyQueueWorkerCommandSucceedsWithSyncQueue(): void
    {
        config([
            'cache.default' => 'array',
            'queue.default' => 'sync',
        ]);

        $exitCode = Artisan::call('app:verify-queue-worker', [
            '--queue' => 'default',
            '--timeout' => 2,
            '--ttl' => 2,
            '--poll' => 1,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString(
            'Queue worker verification succeeded.',
            Artisan::output()
        );
    }
}
