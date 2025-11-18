<?php

namespace App\Providers;

use App\Application\Articles\Services\{ArticleServiceInterface, ArticleService, ArticleKanjiProcessingServiceInterface, ArticleKanjiProcessingService};
use App\Application\Engagement\Services\{EngagementService, EngagementServiceInterface, HashtagServiceInterface, HashtagService};
use App\Application\Users\Services\UserService;
use App\Application\Users\Services\UserServiceInterface;
use Illuminate\Support\ServiceProvider;

class ArticlesServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(ArticleServiceInterface::class, ArticleService::class);
        $this->app->bind(ArticleKanjiProcessingServiceInterface::class, ArticleKanjiProcessingService::class);
        $this->app->bind(EngagementServiceInterface::class, EngagementService::class);
        $this->app->bind(HashtagServiceInterface::class, HashtagService::class);
        $this->app->bind(UserServiceInterface::class, UserService::class);
    }
}
