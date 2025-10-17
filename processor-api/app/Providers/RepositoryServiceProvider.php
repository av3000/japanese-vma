<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Application\Articles\Interfaces\Repositories\ArticleRepositoryInterface;
use App\Infrastructure\Persistence\Repositories\{ArticleRepository, CommentRepository, KanjiRepository, ViewRepository};
use App\Application\Articles\Interfaces\Repositories\KanjiRepositoryInterface;
use App\Application\Articles\Interfaces\Repositories\CommentRepositoryInterface;
use App\Application\Engagement\Interfaces\Repositories\ViewRepositoryInterface;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            ArticleRepositoryInterface::class,
            ArticleRepository::class
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
    }
}
