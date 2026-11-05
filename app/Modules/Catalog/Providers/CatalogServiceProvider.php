<?php

namespace Modules\Catalog\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Shared\Contracts\BookCatalogServiceInterface;
use Modules\Catalog\Services\BookCatalogService;

class CatalogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            BookCatalogServiceInterface::class,
            BookCatalogService::class
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
    }
}
