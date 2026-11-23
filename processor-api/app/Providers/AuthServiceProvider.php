<?php

namespace App\Providers;

use App\Application\Auth\Interfaces\Providers\ActorPermissionCheckerInterface;
use App\Application\Auth\Interfaces\Providers\CurrentUserProviderInterface;
use App\Infrastructure\Auth\Providers\PassportCurrentUserProvider;
use App\Infrastructure\Auth\Providers\SpatieActorPermissionChecker;
use App\Infrastructure\Persistence\Models\AdminActivity;
use App\Infrastructure\Persistence\Models\Article;
use App\Infrastructure\Persistence\Models\Catalogue;
use App\Infrastructure\Persistence\Models\Comment;
use App\Infrastructure\Persistence\Models\Kanji;
use App\Infrastructure\Persistence\Models\Post;
use App\Infrastructure\Persistence\Models\ProcessingState;
use App\Infrastructure\Persistence\Models\Radical;
use App\Infrastructure\Persistence\Models\Sentence;
use App\Infrastructure\Persistence\Models\Uniquehashtag;
use App\Infrastructure\Persistence\Models\User;
use App\Infrastructure\Persistence\Models\Word;
use App\Policies\AdminActivityPolicy;
use App\Policies\ArticleAdminPolicy;
use App\Policies\CatalogueAdminPolicy;
use App\Policies\CommentAdminPolicy;
use App\Policies\HashtagAdminPolicy;
use App\Policies\PostAdminPolicy;
use App\Policies\ProcessingStateAdminPolicy;
use App\Policies\RolePolicy;
use App\Policies\StudyMaterialAdminPolicy;
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
        Catalogue::class => CatalogueAdminPolicy::class,
        Kanji::class => StudyMaterialAdminPolicy::class,
        Radical::class => StudyMaterialAdminPolicy::class,
        Word::class => StudyMaterialAdminPolicy::class,
        Sentence::class => StudyMaterialAdminPolicy::class,
        ProcessingState::class => ProcessingStateAdminPolicy::class,
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

        $this->app->bind(
            ActorPermissionCheckerInterface::class,
            SpatieActorPermissionChecker::class,
        );
    }
}
