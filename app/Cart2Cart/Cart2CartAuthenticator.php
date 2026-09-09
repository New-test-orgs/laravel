<?php

namespace App\Cart2Cart;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class Cart2CartAuthenticator
{
    public const CACHE_KEY = 'cart2cart.access_token';

    public function accessToken(): string
    {
        $cached = Cache::get(self::CACHE_KEY);

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        return $this->login();
    }

    public function refreshAccessToken(): string
    {
        Cache::forget(self::CACHE_KEY);

        return $this->login();
    }

    private function login(): string
    {
        return Cache::lock('cart2cart.login', 15)->block(10, function (): string {
            $cached = Cache::get(self::CACHE_KEY);

            if (is_string($cached) && $cached !== '') {
                return $cached;
            }

            $token = $this->requestAccessToken();

            Cache::put(self::CACHE_KEY, $token, now()->addHour());

            return $token;
        });
    }

    private function requestAccessToken(): string
    {
        try {
            $payload = Cart2CartEnvelope::payload(
                Http::cart2cart()
                    ->retry([100, 500, 1000], 0, function (Throwable $exception): bool {
                        return $exception instanceof ConnectionException
                            || ($exception instanceof RequestException
                                && $exception->response->status() !== 522
                                && ($exception->response->serverError() || $exception->response->status() === 429));
                    })
                    ->throw()
                    ->post('/v1/auth/login', $this->credentials()),
            );
        } catch (ConnectionException) {
            throw new Cart2CartException('Cart2Cart could not be reached. Try again.');
        } catch (RequestException $exception) {
            throw $this->exceptionFrom($exception);
        }

        $token = $payload['access_token'] ?? null;

        if (! is_string($token) || $token === '') {
            throw new Cart2CartException('Cart2Cart did not return an access token.');
        }

        return $token;
    }

    /**
     * @return array{email: string, password: string, remember: false}
     */
    private function credentials(): array
    {
        $email = config('services.cart2cart.email');
        $password = config('services.cart2cart.password');

        if (! is_string($email) || $email === '' || ! is_string($password) || $password === '') {
            throw new Cart2CartException('Cart2Cart credentials are not configured.');
        }

        return [
            'email' => $email,
            'password' => $password,
            'remember' => false,
        ];
    }

    private function exceptionFrom(RequestException $exception): Cart2CartException
    {
        try {
            Cart2CartEnvelope::payload($exception->response);
        } catch (Cart2CartException $cart2CartException) {
            return $cart2CartException;
        }

        return new Cart2CartException('Cart2Cart request failed.', $exception->response->status());
    }
}
