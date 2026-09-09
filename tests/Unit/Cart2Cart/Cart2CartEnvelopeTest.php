<?php

namespace Tests\Unit\Cart2Cart;

use App\Cart2Cart\Cart2CartEnvelope;
use App\Cart2Cart\Cart2CartException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class Cart2CartEnvelopeTest extends TestCase
{
    public function test_returns_payload_when_success_is_true(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://api.newapp.shopping-cart-migration.com/ok' => [
                'success' => true,
                'payload' => ['id' => 42],
                'code' => 200,
            ],
        ]);

        $payload = Cart2CartEnvelope::payload(Http::get('https://api.newapp.shopping-cart-migration.com/ok'));

        $this->assertSame(['id' => 42], $payload);
    }

    public function test_rejects_an_error_envelope_even_when_payload_is_present(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://api.newapp.shopping-cart-migration.com/err' => [
                'success' => false,
                'error' => ['message' => 'No permission'],
                'payload' => ['secret' => 'should-not-be-read'],
                'code' => 403,
            ],
        ]);

        $this->expectException(Cart2CartException::class);
        $this->expectExceptionMessage('No permission');

        Cart2CartEnvelope::payload(Http::get('https://api.newapp.shopping-cart-migration.com/err'));
    }

    public function test_uses_a_string_error_from_the_envelope(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://api.newapp.shopping-cart-migration.com/err' => [
                'success' => false,
                'error' => 'Store credentials are not ready',
                'code' => 422,
            ],
        ]);

        $this->expectException(Cart2CartException::class);
        $this->expectExceptionMessage('Store credentials are not ready');

        Cart2CartEnvelope::payload(Http::get('https://api.newapp.shopping-cart-migration.com/err'));
    }

    public function test_rejects_cloudflare_522_html_without_reading_a_token(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://api.newapp.shopping-cart-migration.com/v1/auth/login' => Http::response(
                'error code: 522',
                522,
                ['Content-Type' => 'text/html'],
            ),
        ]);

        $this->expectException(Cart2CartException::class);
        $this->expectExceptionMessage('Cart2Cart returned HTTP 522 without a JSON envelope. Change C2C_API_BASE_URL.');

        Cart2CartEnvelope::payload(Http::get('https://api.newapp.shopping-cart-migration.com/v1/auth/login'));
    }
}
