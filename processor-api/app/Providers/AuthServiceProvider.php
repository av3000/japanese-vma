<?php

namespace App\Providers;

use App\Application\Auth\Interfaces\Providers\CurrentUserProviderInterface;
use App\Infrastructure\Auth\Providers\PassportCurrentUserProvider;
use App\Infrastructure\Persistence\Models\AdminActivity;
use App\Infrastructure\Persistence\Models\Article;
use App\Infrastructure\Persistence\Models\Comment;
use App\Infrastructure\Persistence\Models\Post;
use App\Infrastructure\Persistence\Models\Uniquehashtag;
use App\Infrastructure\Persistence\Models\User;
use App\Policies\AdminActivityPolicy;
use App\Policies\ArticleAdminPolicy;
use App\Policies\CommentAdminPolicy;
use App\Policies\HashtagAdminPolicy;
use App\Policies\PostAdminPolicy;
use App\Policies\RolePolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Spatie\Permission\Models\Role as SpatieRole;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        User::class => UserPolicy::class,
        SpatieRole::class => RolePolicy::class,
        AdminActivity::class => AdminActivityPolicy::class,
        Article::class => ArticleAdminPolicy::class,
        Post::class => PostAdminPolicy::class,
        Comment::class => CommentAdminPolicy::class,
        Uniquehashtag::class => HashtagAdminPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();
    }

    public function register()
    {
        $this->app->bind(
            CurrentUserProviderInterface::class,
            PassportCurrentUserProvider::class,
        );
    }
}
