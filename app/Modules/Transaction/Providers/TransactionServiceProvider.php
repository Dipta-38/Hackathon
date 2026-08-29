<?php

namespace App\Modules\Transaction\Providers;

use Illuminate\Support\ServiceProvider;

class TransactionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            \App\Modules\Transaction\Services\ConcurrentTransferService::class,
            function ($app) {
                return new \App\Modules\Transaction\Services\ConcurrentTransferService();
            }
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../Views', 'transaction');
        $this->loadMigrationsFrom(__DIR__ . '/../Migrations');
    }
}