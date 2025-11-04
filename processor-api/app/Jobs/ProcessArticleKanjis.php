<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Application\Articles\Services\ArticleKanjiProcessingService;

class ProcessArticleKanjis implements ShouldQueue
{
    // use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        private string $articleUid;
    }

    /**
     * Execute the job.
     */
    public function handle(ArticleKanjiProcessingService $service): void
    {
        $service->processArticleKanjis($this->articleUid);
    }
}
