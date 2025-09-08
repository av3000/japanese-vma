<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\Articles\Repositories\ArticleRepositoryInterface;
use App\Infrastructure\Persistence\Repositories\ArticleRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ArticleRepositoryInterface::class,
            ArticleRepository::class
        );
    }
}
