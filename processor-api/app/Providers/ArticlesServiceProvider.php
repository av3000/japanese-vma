<?php

namespace App\Providers;

use App\Application\Articles\Services\{ArticleServiceInterface, ArticleService, ArticleKanjiProcessingServiceInterface, ArticleKanjiProcessingService};
use App\Application\Engagement\Services\{EngagementService, EngagementServiceInterface, HashtagServiceInterface, HashtagService};
use App\Application\JapaneseMaterial\Kanjis\Services\{KanjiExtractionService, KanjiExtractionServiceInterface, KanjiServiceInterface, KanjiService};
use App\Application\LastOperations\Services\LastOperationService;
use App\Application\LastOperations\Services\LastOperationServiceInterface;
use App\Application\Users\Services\RoleService;
use App\Application\Users\Services\RoleServiceInterface;
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
        $this->app->bind(RoleServiceInterface::class, RoleService::class);
        $this->app->bind(KanjiServiceInterface::class, KanjiService::class);

        $this->app->bind(KanjiExtractionServiceInterface::class, KanjiExtractionService::class);
        $this->app->bind(LastOperationServiceInterface::class, LastOperationService::class);
    }
}
