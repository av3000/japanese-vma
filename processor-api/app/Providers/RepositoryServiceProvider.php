<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Application\Articles\Interfaces\Repositories\ArticleRepositoryInterface;
use App\Infrastructure\Persistence\Repositories\ArticleRepository;
use App\Application\Articles\Interfaces\Repositories\KanjiRepositoryInterface;
use App\Infrastructure\Persistence\Repositories\KanjiRepository;
use App\Application\Articles\Interfaces\Repositories\CommentRepositoryInterface;
use App\Infrastructure\Persistence\Repositories\CommentRepository;

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
    }
}
