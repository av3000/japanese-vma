<?php

namespace App\Providers;

use App\Application\Articles\Interfaces\Repositories\ArticleRepositoryInterface;

use App\Application\Catalogues\Interfaces\Repositories\CatalogueItemRepositoryInterface;
use App\Application\Catalogues\Interfaces\Repositories\CatalogueRepositoryInterface;
use App\Application\Comments\Interfaces\Repositories\CommentRepositoryInterface;
use App\Application\Engagement\Interfaces\Repositories\DownloadRepositoryInterface;
use App\Application\Engagement\Interfaces\Repositories\HashtagRepositoryInterface;
use App\Application\Engagement\Interfaces\Repositories\LikeRepositoryInterface;
use App\Application\Engagement\Interfaces\Repositories\ViewRepositoryInterface;
use App\Application\JapaneseMaterial\Kanjis\Interfaces\Repositories\KanjiRepositoryInterface;
use App\Application\JapaneseMaterial\Radicals\Interfaces\Repositories\RadicalRepositoryInterface;
use App\Application\JapaneseMaterial\Sentences\Interfaces\Repositories\SentenceRepositoryInterface;
use App\Application\JapaneseMaterial\Words\Interfaces\Repositories\WordRepositoryInterface;
use App\Application\LastOperations\Interfaces\Repositories\LastOperationRepositoryInterface;
use App\Application\Users\Interfaces\Repositories\RoleRepositoryInterface;
use App\Application\Users\Interfaces\Repositories\UserRepositoryInterface;
use App\Infrastructure\Persistence\Repositories\ArticleRepository;
use App\Infrastructure\Persistence\Repositories\CatalogueItemRepository;
use App\Infrastructure\Persistence\Repositories\CatalogueRepository;
use App\Infrastructure\Persistence\Repositories\CommentRepository;
use App\Infrastructure\Persistence\Repositories\DownloadRepository;
use App\Infrastructure\Persistence\Repositories\HashtagRepository;
use App\Infrastructure\Persistence\Repositories\KanjiRepository;
use App\Infrastructure\Persistence\Repositories\LastOperationRepository;
use App\Infrastructure\Persistence\Repositories\LikeRepository;
use App\Infrastructure\Persistence\Repositories\RadicalRepository;
use App\Infrastructure\Persistence\Repositories\RoleRepository;
use App\Infrastructure\Persistence\Repositories\SentenceRepository;
use App\Infrastructure\Persistence\Repositories\UserRepository;
use App\Infrastructure\Persistence\Repositories\ViewRepository;
use App\Infrastructure\Persistence\Repositories\WordRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            ArticleRepositoryInterface::class,
            ArticleRepository::class
        );

        $this->app->singleton(
            LastOperationRepositoryInterface::class,
            LastOperationRepository::class
        );

        $this->app->singleton(
            KanjiRepositoryInterface::class,
            KanjiRepository::class
        );

        $this->app->singleton(
            RadicalRepositoryInterface::class,
            RadicalRepository::class
        );

        $this->app->singleton(
            SentenceRepositoryInterface::class,
            SentenceRepository::class
        );

        $this->app->singleton(
            WordRepositoryInterface::class,
            WordRepository::class
        );

        $this->app->singleton(
            CommentRepositoryInterface::class,
            CommentRepository::class
        );

        $this->app->singleton(
            ViewRepositoryInterface::class,
            ViewRepository::class
        );

        $this->app->singleton(
            LikeRepositoryInterface::class,
            LikeRepository::class
        );

        $this->app->singleton(
            DownloadRepositoryInterface::class,
            DownloadRepository::class
        );

        $this->app->singleton(
            HashtagRepositoryInterface::class,
            HashtagRepository::class
        );

        $this->app->singleton(
            UserRepositoryInterface::class,
            UserRepository::class
        );

        $this->app->singleton(
            RoleRepositoryInterface::class,
            RoleRepository::class
        );

        $this->app->singleton(
            CatalogueRepositoryInterface::class,
            CatalogueRepository::class
        );

        $this->app->singleton(
            CatalogueItemRepositoryInterface::class,
            CatalogueItemRepository::class
        );
    }
}
