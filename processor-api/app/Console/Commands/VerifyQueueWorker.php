<?php

namespace App\Console\Commands;

use App\Application\Operations\Services\QueueWorkerVerifierInterface;
use Illuminate\Console\Command;

class VerifyQueueWorker extends Command
{
    protected $signature = 'app:verify-queue-worker
        {--queue=default : Queue name that the smoke job should use}
        {--timeout=30 : Seconds to wait for the remote worker to consume the job}
        {--ttl=120 : Seconds to keep the cache marker after successful processing}
        {--poll=1 : Seconds between cache polling attempts}';

    protected $description = 'Dispatch a queue smoke job and wait for the shared cache marker to confirm a live worker consumed it';

    public function handle(QueueWorkerVerifierInterface $verifier): int
    {
        $queue = (string) $this->option('queue');
        $timeoutSeconds = max(1, (int) $this->option('timeout'));
        $ttlSeconds = max($timeoutSeconds, (int) $this->option('ttl'));
        $pollSeconds = max(1, (int) $this->option('poll'));

        $result = $verifier->verify(
            queue: $queue,
            timeoutSeconds: $timeoutSeconds,
            ttlSeconds: $ttlSeconds,
            pollMilliseconds: $pollSeconds * 1000,
            onDispatched: function (string $cacheKey) use ($queue): void {
                $this->info("Dispatched queue smoke job on [{$queue}] and waiting for marker [{$cacheKey}].");
            },
        );

        if ($result->succeeded) {
            $this->info('Queue worker verification succeeded.');

            return self::SUCCESS;
        }

        $this->error("Queue worker verification timed out after {$timeoutSeconds} seconds.");

        return self::FAILURE;
    }
}
