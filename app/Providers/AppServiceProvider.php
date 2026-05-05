<?php

namespace App\Providers;

use App\Observers\TenantObserver;
use App\Observers\UserObserver;
use App\Services\Tenancy\TenantResolver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TenantResolver::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \App\Models\Tenant::observe(TenantObserver::class);
        \App\Models\User::observe(UserObserver::class);
    }
}
