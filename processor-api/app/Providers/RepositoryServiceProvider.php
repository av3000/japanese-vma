<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Application\Articles\Interfaces\Repositories\ArticleRepositoryInterface;
use App\Infrastructure\Persistence\Repositories\{ArticleRepository, CommentRepository, KanjiRepository, ViewRepository, LikeRepository, DownloadRepository, HashtagRepository, RoleRepository, UserRepository, CustomListRepository, LastOperationRepository};
use App\Application\Engagement\Interfaces\Repositories\{ViewRepositoryInterface, LikeRepositoryInterface, DownloadRepositoryInterface, HashtagRepositoryInterface};
use App\Application\Users\Interfaces\Repositories\UserRepositoryInterface;
use App\Application\Comments\Interfaces\Repositories\CommentRepositoryInterface;
use App\Application\Users\Interfaces\Repositories\RoleRepositoryInterface;
use App\Application\CustomLists\Interfaces\Repositories\CustomListRepositoryInterface;
use App\Application\JapaneseMaterial\Kanjis\Interfaces\Repositories\KanjiRepositoryInterface;
use App\Application\LastOperations\Interfaces\Repositories\LastOperationRepositoryInterface;

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
            CustomListRepositoryInterface::class,
            CustomListRepository::class
        );
    }
}
