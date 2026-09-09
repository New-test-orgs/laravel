<?php

namespace Tests\Unit\Cart2Cart;

use App\Cart2Cart\Cart2CartAuthenticator;
use App\Cart2Cart\Cart2CartException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Tests\TestCase;

class Cart2CartAuthenticatorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.cart2cart.email' => 'c2c@example.com',
            'services.cart2cart.password' => 'secret',
        ]);
    }

    public function test_logs_in_with_email_and_password_and_caches_the_access_token(): void
    {
        $this->fakeLogin();

        $token = (new Cart2CartAuthenticator)->accessToken();

        $this->assertSame('12|c2c-access', $token);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.newapp.shopping-cart-migration.com/v1/auth/login'
            && $request['email'] === 'c2c@example.com'
            && $request['password'] === 'secret'
            && $request['remember'] === false
            && ! $request->hasHeader('Authorization'));
    }

    public function test_reuses_the_cached_access_token_instead_of_logging_in_again(): void
    {
        $this->fakeLogin();

        $authenticator = new Cart2CartAuthenticator;

        $this->assertSame('12|c2c-access', $authenticator->accessToken());
        $this->assertSame('12|c2c-access', $authenticator->accessToken());
        Http::assertSentCount(1);
    }

    public function test_rejects_missing_credentials(): void
    {
        config([
            'services.cart2cart.email' => null,
            'services.cart2cart.password' => null,
        ]);
        Http::preventStrayRequests();

        $this->expectException(Cart2CartException::class);
        $this->expectExceptionMessage('Cart2Cart credentials are not configured.');

        (new Cart2CartAuthenticator)->accessToken();
    }

    public function test_rejects_a_login_error_envelope(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://api.newapp.shopping-cart-migration.com/v1/auth/login' => Http::response([
                'success' => false,
                'error' => ['message' => 'Invalid credentials'],
                'code' => 401,
            ], 401),
        ]);

        $this->expectException(Cart2CartException::class);
        $this->expectExceptionMessage('Invalid credentials');

        (new Cart2CartAuthenticator)->accessToken();
    }

    public function test_refreshes_the_cached_token_by_logging_in_again(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://api.newapp.shopping-cart-migration.com/v1/auth/login' => Http::sequence()
                ->push([
                    'success' => true,
                    'payload' => ['access_token' => '12|first'],
                    'code' => 200,
                ])
                ->push([
                    'success' => true,
                    'payload' => ['access_token' => '13|second'],
                    'code' => 200,
                ]),
        ]);

        $authenticator = new Cart2CartAuthenticator;

        $this->assertSame('12|first', $authenticator->accessToken());
        $this->assertSame('13|second', $authenticator->refreshAccessToken());
        Http::assertSentCount(2);
    }

    public function test_does_not_treat_cloudflare_522_html_as_an_access_token(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://api.newapp.shopping-cart-migration.com/v1/auth/login' => Http::response(
                'error code: 522',
                522,
                ['Content-Type' => 'text/html'],
            ),
        ]);

        try {
            (new Cart2CartAuthenticator)->accessToken();
            $this->fail('Login against a 522 HTML response must not return a token.');
        } catch (Cart2CartException $exception) {
            $this->assertSame('Cart2Cart returned HTTP 522 without a JSON envelope. Change C2C_API_BASE_URL.', $exception->getMessage());
        }

        Http::assertSentCount(1);
        $this->assertNull(Cache::get(Cart2CartAuthenticator::CACHE_KEY));
    }

    public function test_rejects_a_connection_failure_when_logging_in(): void
    {
        Sleep::fake();
        Http::preventStrayRequests();
        Http::fake([
            'https://api.newapp.shopping-cart-migration.com/v1/auth/login' => Http::failedConnection(),
        ]);

        $this->expectException(Cart2CartException::class);
        $this->expectExceptionMessage('Cart2Cart could not be reached. Try again.');

        (new Cart2CartAuthenticator)->accessToken();
    }

    private function fakeLogin(string $accessToken = '12|c2c-access'): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://api.newapp.shopping-cart-migration.com/v1/auth/login' => Http::response([
                'success' => true,
                'payload' => ['access_token' => $accessToken],
                'code' => 200,
            ]),
        ]);
    }
}
