<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class QueueSmokeMarkerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly string $cacheKey,
        private readonly int $ttlSeconds = 120,
    ) {}

    public function handle(): void
    {
        Cache::put($this->cacheKey, true, now()->addSeconds($this->ttlSeconds));
    }
}
