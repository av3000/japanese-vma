<?php

namespace App\Providers;

use App\Application\Auth\Interfaces\Services\AuthSessionServiceInterface;
use App\Infrastructure\Auth\Services\AuthSessionService;
use App\Infrastructure\Persistence\Models\User;
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
            AuthSessionServiceInterface::class,
            AuthSessionService::class
        );
    }
}
