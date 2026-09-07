<?php

namespace App\Providers;

use App\Scripts\DemoMigrationScript;
use App\Scripts\MigrationScript;
use Illuminate\Support\Facades\Http;
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
        Http::macro('github', function () {
            $request = Http::baseUrl('https://api.github.com')
                ->accept('application/vnd.github+json')
                ->withHeaders(['X-GitHub-Api-Version' => '2022-11-28'])
                ->connectTimeout(3)
                ->timeout(10);

            $token = config('services.github.token');

            if (is_string($token) && $token !== '') {
                $request = $request->withToken($token);
            }

            return $request;
        });
    }
}
