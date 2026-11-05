<?php

namespace Modules\Membership\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Shared\Contracts\MemberCirculationServiceInterface;
use Modules\Membership\Services\MemberCirculationService;

class MembershipServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            MemberCirculationServiceInterface::class,
            MemberCirculationService::class
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
    }
}
