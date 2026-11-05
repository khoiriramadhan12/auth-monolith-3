<?php

namespace Modules\Circulation\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Modules\Circulation\Events\BookBorrowed;
use Modules\Circulation\Listeners\LogBookBorrowed;
use Modules\Circulation\Services\BorrowingService;

class CirculationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(BorrowingService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        Event::listen(
            BookBorrowed::class,
            LogBookBorrowed::class
        );
    }
}
