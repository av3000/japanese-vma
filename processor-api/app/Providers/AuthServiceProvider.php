<?php

namespace App\Providers;

use App\Application\Auth\Interfaces\Services\AuthSessionServiceInterface;
use App\Infrastructure\Auth\Services\AuthSessionService;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Model' => 'App\Policies\ModelPolicy',
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
