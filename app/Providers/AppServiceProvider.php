<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register BookSearchContext with filter strategies
        $this->app->singleton(\App\Services\BookSearch\BookSearchContext::class, function ($app) {
            return new \App\Services\BookSearch\BookSearchContext([
                new \App\Services\BookSearch\Strategies\KeywordFilter(),
                new \App\Services\BookSearch\Strategies\StatusFilter(),
                new \App\Services\BookSearch\Strategies\CategoryFilter(),
                new \App\Services\BookSearch\Strategies\YearRangeFilter(),
                new \App\Services\BookSearch\Strategies\SortStrategy(),
            ]);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register CORS middleware for frontend/backend separation
        // This allows cross-origin requests from port 8000 (frontend) to port 8001 (backend)
        if (config('app.api_url') !== config('app.url')) {
            // Different URLs detected - CORS is needed for separation
            $this->registerCorsMacro();
        }

        // Register API authentication and permission middleware aliases
        $this->registerMiddlewareAliases();
    }

    /**
     * Register CORS macro for handling cross-origin requests
     */
    private function registerCorsMacro(): void
    {
        \Illuminate\Routing\Route::macro('cors', function () {
            return $this->middleware('cors');
        });
    }

    /**
     * Register middleware aliases for API security
     */
    private function registerMiddlewareAliases(): void
    {
        // Register API token authentication middleware
        app('router')->aliasMiddleware('api_token_auth', \App\Http\Middleware\ApiTokenAuth::class);
        
        // Register book permission middleware
        app('router')->aliasMiddleware('check_book_permission', \App\Http\Middleware\CheckBookPermission::class);
    }
}
