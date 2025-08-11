<?php

namespace App\Providers;

use App\Domain\Articles\Actions\Retrieval\GetArticlesAction;
use App\Domain\Articles\Interfaces\Actions\ArticleListActionInterface;
use App\Domain\Articles\Interfaces\Policies\ArticleViewPolicyInterface;
use App\Domain\Articles\Policies\ArticleViewPolicy;
use Illuminate\Support\ServiceProvider;

class ArticlesServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(
            ArticleListActionInterface::class,
            GetArticlesAction::class
        );

        $this->app->bind(
            GetArticleDetailActionInterface::class,
            GetArticleDetailAction::class
        );

        $this->app->bind(
            ArticleViewPolicyInterface::class,
            ArticleViewPolicy::class
        );

        $this->app->bind(
            DeleteArticleActionInterface::class,
            DeleteArticleAction::class
        );

         $this->app->bind(
            CreateArticleActionInterface::class,
            CreateArticleAction::class
        );

        $this->app->bind(
            UpdateArticleActionInterface::class,
            UpdateArticleAction::class
        );
    }
}
