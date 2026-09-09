<?php

namespace Tests\Unit\Cart2Cart;

use App\Cart2Cart\Cart2CartAuthenticator;
use App\Cart2Cart\Cart2CartClient;
use App\Cart2Cart\Cart2CartException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Tests\TestCase;

class Cart2CartClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.cart2cart.email' => 'c2c@example.com',
            'services.cart2cart.password' => 'secret',
        ]);
    }

    public function test_fetches_migration_properties_with_the_c2c_bearer_token(): void
    {
        $this->fakeCart2Cart([
            'https://api.newapp.shopping-cart-migration.com/v1/admin/migrations/42/properties' => [
                'success' => true,
                'payload' => ['id' => 42, 'entities' => ['products' => 10]],
                'code' => 200,
            ],
        ]);

        $properties = $this->client()->migrationProperties(42);

        $this->assertSame(['id' => 42, 'entities' => ['products' => 10]], $properties);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.newapp.shopping-cart-migration.com/v1/admin/migrations/42/properties'
            && $request->hasHeader('Authorization', 'Bearer 12|c2c-access'));
    }

    public function test_lists_migrations_with_page_and_filters(): void
    {
        $this->fakeCart2Cart([
            'https://api.newapp.shopping-cart-migration.com/v1/admin/migrations?page=2&per_page=10&status=running' => [
                'success' => true,
                'payload' => ['data' => [['id' => 42]]],
                'code' => 200,
            ],
        ]);

        $page = $this->client()->migrations([
            'page' => 2,
            'per_page' => 10,
            'status' => 'running',
        ]);

        $this->assertSame(['data' => [['id' => 42]]], $page);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.newapp.shopping-cart-migration.com/v1/admin/migrations?page=2&per_page=10&status=running');
    }

    public function test_returns_source_and_target_store_credentials_for_the_connect_script(): void
    {
        $this->fakeCart2Cart([
            'https://api.newapp.shopping-cart-migration.com/v1/admin/migrations/42/stores/access' => [
                'success' => true,
                'payload' => [
                    'source' => [
                        'cart_id' => 'Shopify',
                        'url' => 'https://source.example',
                        'account_email' => 'source@example.com',
                        'account_token' => 'source-store-token',
                        'connection' => 'file',
                        'cart_version' => '2.0',
                        'vars' => ['bridge' => 'abc'],
                        'validated' => true,
                    ],
                    'target' => [
                        'cart_id' => 'CsvToCart',
                        'url' => 'https://target.example',
                        'account_email' => 'target@example.com',
                        'account_token' => 'target-store-token',
                        'connection' => 'api',
                        'cart_version' => null,
                        'vars' => [],
                        'validated' => false,
                    ],
                ],
                'code' => 200,
            ],
        ]);

        $access = $this->client()->storeAccess(42);

        $this->assertSame('Shopify', $access->source->cartId);
        $this->assertSame('Shopify', $access->source->cartLabel());
        $this->assertSame('CsvToCart', $access->target->cartLabel());
        $this->assertSame('https://source.example', $access->source->url);
        $this->assertSame('source@example.com', $access->source->accountEmail);
        $this->assertSame('source-store-token', $access->source->accountToken);
        $this->assertSame('file', $access->source->connection);
        $this->assertSame('2.0', $access->source->cartVersion);
        $this->assertSame(['bridge' => 'abc'], $access->source->vars);
        $this->assertTrue($access->source->validated);
        $this->assertSame('target-store-token', $access->target->accountToken);
        $this->assertFalse($access->target->validated);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.newapp.shopping-cart-migration.com/v1/admin/migrations/42/stores/access'
            && $request->hasHeader('Authorization', 'Bearer 12|c2c-access'));
    }

    public function test_does_not_use_the_c2c_access_token_as_a_store_credential(): void
    {
        $this->fakeCart2Cart([
            'https://api.newapp.shopping-cart-migration.com/v1/admin/migrations/42/stores/access' => [
                'success' => true,
                'payload' => [
                    'source' => ['account_token' => 'source-store-token'],
                    'target' => ['account_token' => 'target-store-token'],
                ],
                'code' => 200,
            ],
        ]);

        $access = $this->client()->storeAccess(42);

        $this->assertSame('source-store-token', $access->source->accountToken);
        $this->assertSame('target-store-token', $access->target->accountToken);
        $this->assertNotSame('12|c2c-access', $access->source->accountToken);
        $this->assertNotSame('12|c2c-access', $access->target->accountToken);
    }

    public function test_logs_in_again_and_retries_once_after_401(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://api.newapp.shopping-cart-migration.com/v1/auth/login' => Http::sequence()
                ->push([
                    'success' => true,
                    'payload' => ['access_token' => '12|stale'],
                    'code' => 200,
                ])
                ->push([
                    'success' => true,
                    'payload' => ['access_token' => '13|fresh'],
                    'code' => 200,
                ]),
            'https://api.newapp.shopping-cart-migration.com/v1/admin/migrations/42/properties' => Http::sequence()
                ->push([
                    'success' => false,
                    'error' => ['message' => 'Unauthenticated'],
                    'code' => 401,
                ], 401)
                ->push([
                    'success' => true,
                    'payload' => ['id' => 42],
                    'code' => 200,
                ]),
        ]);

        $properties = $this->client()->migrationProperties(42);

        $this->assertSame(['id' => 42], $properties);

        $propertyTokens = collect(Http::recorded())
            ->map(fn (array $pair): Request => $pair[0])
            ->filter(fn (Request $request): bool => str_contains($request->url(), '/properties'))
            ->map(fn (Request $request): ?string => $request->header('Authorization')[0] ?? null)
            ->values()
            ->all();

        $this->assertSame(['Bearer 12|stale', 'Bearer 13|fresh'], $propertyTokens);
    }

    public function test_rejects_an_error_envelope_without_reading_payload(): void
    {
        $this->fakeCart2Cart([
            'https://api.newapp.shopping-cart-migration.com/v1/admin/migrations/42/properties' => Http::response([
                'success' => false,
                'error' => ['message' => 'No permission'],
                'payload' => ['secret' => 'should-not-be-read'],
                'code' => 403,
            ], 403),
        ]);

        $this->expectException(Cart2CartException::class);
        $this->expectExceptionMessage('No permission');

        $this->client()->migrationProperties(42);
    }

    public function test_rejects_a_connection_failure(): void
    {
        Sleep::fake();
        $this->fakeCart2Cart([
            'https://api.newapp.shopping-cart-migration.com/v1/admin/migrations/42/properties' => Http::failedConnection(),
        ]);

        $this->expectException(Cart2CartException::class);
        $this->expectExceptionMessage('Cart2Cart could not be reached. Try again.');

        $this->client()->migrationProperties(42);
    }

    public function test_does_not_login_on_every_admin_request(): void
    {
        $this->fakeCart2Cart([
            'https://api.newapp.shopping-cart-migration.com/v1/admin/migrations/42/properties' => [
                'success' => true,
                'payload' => ['id' => 42],
                'code' => 200,
            ],
        ]);

        $client = $this->client();
        $client->migrationProperties(42);
        $client->migrationProperties(42);

        $loginCount = collect(Http::recorded())
            ->map(fn (array $pair): Request => $pair[0])
            ->filter(fn (Request $request): bool => str_contains($request->url(), '/v1/auth/login'))
            ->count();

        $this->assertSame(1, $loginCount);
    }

    private function client(): Cart2CartClient
    {
        return new Cart2CartClient(new Cart2CartAuthenticator);
    }

    /**
     * @param  array<string, mixed>  $fakes
     */
    private function fakeCart2Cart(array $fakes): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://api.newapp.shopping-cart-migration.com/v1/auth/login' => [
                'success' => true,
                'payload' => ['access_token' => '12|c2c-access'],
                'code' => 200,
            ],
            ...$fakes,
        ]);
    }
}
