<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register() {}

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $sharedContext = array_filter([
            'app_env' => app()->environment(),
            'app_release' => config('app.release'),
            'request_id' => app()->runningInConsole() ? null : (request()->headers->get('X-Request-Id') ?: (string) Str::uuid()),
            'request_path' => app()->runningInConsole() ? null : request()->path(),
        ], static fn($value) => $value !== null && $value !== '');

        $logger = Log::getFacadeRoot();

        if (method_exists($logger, 'shareContext')) {
            $logger->shareContext($sharedContext);
        } else {
            $logger->withContext($sharedContext);
        }

        // TODO: analyse macro purpose and how to refactor it.
        Builder::macro('whereLike', function ($attributes, string $searchTerm) {
            $this->where(function (Builder $query) use ($attributes, $searchTerm) {
                foreach (array_wrap($attributes) as $attribute) {
                    $query->when(
                        str_contains($attribute, '.'),
                        function (Builder $query) use ($attribute, $searchTerm) {
                            [$relationName, $relationAttribute] = explode('.', $attribute);

                            $query->orWhereHas($relationName, function (Builder $query) use ($relationAttribute, $searchTerm) {
                                $query->where($relationAttribute, 'LIKE', "%{$searchTerm}%");
                            });
                        },
                        function (Builder $query) use ($attribute, $searchTerm) {
                            $query->orWhere($attribute, 'LIKE', "%{$searchTerm}%");
                        }
                    );
                }
            });

            return $this;
        });
    }
}
