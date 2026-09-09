<?php

namespace App\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
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

        Http::macro('cart2cart', function (?string $accessToken = null) {
            $request = Http::baseUrl((string) config('services.cart2cart.base_url'))
                ->acceptJson()
                ->asJson()
                ->connectTimeout(3)
                ->timeout(10);

            if (is_string($accessToken) && $accessToken !== '') {
                $request = $request->withToken($accessToken);
            }

            return $request;
        });
    }
}
