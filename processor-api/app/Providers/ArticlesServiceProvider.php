<?php

namespace App\Providers;

use App\Application\Articles\Services\{ArticleServiceInterface, ArticleService};
use Illuminate\Support\ServiceProvider;

class ArticlesServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(ArticleServiceInterface::class, ArticleService::class);
    }
}
