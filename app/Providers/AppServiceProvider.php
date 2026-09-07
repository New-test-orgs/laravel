<?php

namespace App\Providers;

use App\Scripts\DemoMigrationScript;
use App\Scripts\MigrationScript;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(MigrationScript::class, DemoMigrationScript::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
