<?php

namespace Tests\Unit\Cart2Cart;

use App\Cart2Cart\Cart2CartStoreAccess;
use App\Cart2Cart\Cart2CartStoreCredentials;
use App\StoreSide;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class Cart2CartStoreAccessTest extends TestCase
{
    public function test_returns_the_credentials_for_the_selected_store_side(): void
    {
        $access = $this->access();

        $this->assertSame('source-store-token', $access->for(StoreSide::Source)->accountToken);
        $this->assertSame('target-store-token', $access->for(StoreSide::Target)->accountToken);
    }

    /**
     * @return array<string, array{0: StoreSide}>
     */
    public static function storeSides(): array
    {
        return [
            'source' => [StoreSide::Source],
            'target' => [StoreSide::Target],
        ];
    }

    #[DataProvider('storeSides')]
    public function test_serializes_credentials_for_scripts(StoreSide $side): void
    {
        $credentials = $this->access()->for($side);

        $this->assertSame([
            'cart_id' => $side === StoreSide::Source ? 'Shopify' : 'CsvToCart',
            'url' => $side === StoreSide::Source ? 'https://source.example' : 'https://target.example',
            'account_email' => $side === StoreSide::Source ? 'source@example.com' : 'target@example.com',
            'account_token' => $side === StoreSide::Source ? 'source-store-token' : 'target-store-token',
            'connection' => $side === StoreSide::Source ? 'file' : 'api',
            'cart_version' => $side === StoreSide::Source ? '2.0' : null,
            'vars' => $side === StoreSide::Source ? ['bridge' => 'abc'] : [],
            'validated' => $side === StoreSide::Source,
        ], $credentials->toArray());
    }

    private function access(): Cart2CartStoreAccess
    {
        return new Cart2CartStoreAccess(
            source: Cart2CartStoreCredentials::fromPayload([
                'cart_id' => 'Shopify',
                'url' => 'https://source.example',
                'account_email' => 'source@example.com',
                'account_token' => 'source-store-token',
                'connection' => 'file',
                'cart_version' => '2.0',
                'vars' => ['bridge' => 'abc'],
                'validated' => true,
            ]),
            target: Cart2CartStoreCredentials::fromPayload([
                'cart_id' => 'CsvToCart',
                'url' => 'https://target.example',
                'account_email' => 'target@example.com',
                'account_token' => 'target-store-token',
                'connection' => 'api',
                'cart_version' => null,
                'vars' => [],
                'validated' => false,
            ]),
        );
    }
}
