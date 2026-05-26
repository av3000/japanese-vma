<?php

namespace App\Providers;

use App\Application\Articles\Services\ArticleKanjiProcessingService;
use App\Application\Articles\Services\ArticleKanjiProcessingServiceInterface;
use App\Application\Articles\Services\ArticlePdfExportService;
use App\Application\Articles\Services\ArticlePdfExportServiceInterface;
use App\Application\Articles\Services\ArticleService;
use App\Application\Articles\Services\ArticleServiceInterface;
use App\Application\Catalogues\Services\CataloguePdfExportService;
use App\Application\Catalogues\Services\CataloguePdfExportServiceInterface;
use App\Application\Catalogues\Services\CatalogueService;
use App\Application\Catalogues\Services\CatalogueServiceInterface;
use App\Application\Engagement\Services\EngagementService;
use App\Application\Engagement\Services\EngagementServiceInterface;
use App\Application\Engagement\Services\HashtagService;
use App\Application\Engagement\Services\HashtagServiceInterface;
use App\Application\JapaneseMaterial\Kanjis\Services\KanjiExtractionService;
use App\Application\JapaneseMaterial\Kanjis\Services\KanjiExtractionServiceInterface;
use App\Application\JapaneseMaterial\Kanjis\Services\KanjiService;
use App\Application\JapaneseMaterial\Kanjis\Services\KanjiServiceInterface;
use App\Application\JapaneseMaterial\Radicals\Services\RadicalService;
use App\Application\JapaneseMaterial\Radicals\Services\RadicalServiceInterface;
use App\Application\JapaneseMaterial\Words\Interfaces\Repositories\WordRepositoryInterface;
use App\Application\JapaneseMaterial\Words\Services\WordExtractionService;
use App\Application\JapaneseMaterial\Words\Services\WordExtractionServiceInterface;
use App\Application\LastOperations\Services\LastOperationService;
use App\Application\LastOperations\Services\LastOperationServiceInterface;
use App\Application\Users\Services\RoleService;
use App\Application\Users\Services\RoleServiceInterface;
use App\Application\Users\Services\UserService;
use App\Application\Users\Services\UserServiceInterface;
use App\Infrastructure\Persistence\Repositories\WordRepository;

use Illuminate\Support\ServiceProvider;

class ArticlesServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(ArticleServiceInterface::class, ArticleService::class);
        $this->app->bind(ArticleKanjiProcessingServiceInterface::class, ArticleKanjiProcessingService::class);
        $this->app->bind(ArticlePdfExportServiceInterface::class, ArticlePdfExportService::class);
        $this->app->bind(CataloguePdfExportServiceInterface::class, CataloguePdfExportService::class);
        $this->app->bind(CatalogueServiceInterface::class, CatalogueService::class);
        $this->app->bind(EngagementServiceInterface::class, EngagementService::class);
        $this->app->bind(HashtagServiceInterface::class, HashtagService::class);
        $this->app->bind(UserServiceInterface::class, UserService::class);
        $this->app->bind(RoleServiceInterface::class, RoleService::class);
        $this->app->bind(KanjiServiceInterface::class, KanjiService::class);
        $this->app->bind(RadicalServiceInterface::class, RadicalService::class);

        $this->app->bind(KanjiExtractionServiceInterface::class, KanjiExtractionService::class);
        $this->app->bind(WordRepositoryInterface::class, WordRepository::class);
        $this->app->bind(WordExtractionServiceInterface::class, WordExtractionService::class);
        $this->app->bind(LastOperationServiceInterface::class, LastOperationService::class);
    }
}
