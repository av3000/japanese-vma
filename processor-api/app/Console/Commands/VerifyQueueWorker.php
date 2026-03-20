<?php

namespace App\Console\Commands;

use App\Jobs\QueueSmokeMarkerJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class VerifyQueueWorker extends Command
{
    protected $signature = 'app:verify-queue-worker
        {--queue=default : Queue name that the smoke job should use}
        {--timeout=30 : Seconds to wait for the remote worker to consume the job}
        {--ttl=120 : Seconds to keep the cache marker after successful processing}
        {--poll=1 : Seconds between cache polling attempts}';

    protected $description = 'Dispatch a queue smoke job and wait for the shared cache marker to confirm a live worker consumed it';

    public function handle(): int
    {
        $queue = (string) $this->option('queue');
        $timeoutSeconds = max(1, (int) $this->option('timeout'));
        $ttlSeconds = max($timeoutSeconds, (int) $this->option('ttl'));
        $pollSeconds = max(1, (int) $this->option('poll'));
        $cacheKey = 'queue_smoke:'.Str::uuid()->toString();

        Cache::forget($cacheKey);

        QueueSmokeMarkerJob::dispatch($cacheKey, $ttlSeconds)->onQueue($queue);

        $this->info("Dispatched queue smoke job on [{$queue}] and waiting for marker [{$cacheKey}].");

        $deadline = now()->addSeconds($timeoutSeconds);

        while (now()->lessThanOrEqualTo($deadline)) {
            if (Cache::has($cacheKey)) {
                $this->info('Queue worker verification succeeded.');

                return self::SUCCESS;
            }

            sleep($pollSeconds);
        }

        $this->error("Queue worker verification timed out after {$timeoutSeconds} seconds.");

        return self::FAILURE;
    }
}
