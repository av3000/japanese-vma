<?php

namespace App\Providers;

use App\Application\Articles\Services\{ArticleServiceInterface, ArticleService, ArticleKanjiProcessingServiceInterface, ArticleKanjiProcessingService};
use App\Application\Engagement\Services\{EngagementService, EngagementServiceInterface};
use Illuminate\Support\ServiceProvider;

class ArticlesServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(ArticleServiceInterface::class, ArticleService::class);
        $this->app->bind(ArticleKanjiProcessingServiceInterface::class, ArticleKanjiProcessingService::class);
        $this->app->bind(EngagementServiceInterface::class, EngagementService::class);
    }
}
