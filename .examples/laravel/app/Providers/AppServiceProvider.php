<?php

declare(strict_types=1);

namespace App\Providers;

use App\Search\BlogReindexProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app->singleton(BlogReindexProvider::class, fn () => new BlogReindexProvider());

        $this->app->tag(BlogReindexProvider::class, 'cmsig_seal.reindex_provider');
    }
}
