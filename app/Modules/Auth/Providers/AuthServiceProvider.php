<?php

namespace App\Modules\Auth\Providers;

use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__."/../Routes/web.php");
        $this->loadViewsFrom(__DIR__."/../Views", "auth");
        $this->loadMigrationsFrom(__DIR__."/../Database/Migrations");
    }
}
