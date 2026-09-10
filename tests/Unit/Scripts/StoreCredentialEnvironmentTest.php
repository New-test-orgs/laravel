<?php

namespace Tests\Unit\Scripts;

use App\Cart2Cart\Cart2CartStoreAccess;
use App\Cart2Cart\Cart2CartStoreCredentials;
use App\Scripts\StoreCredentialEnvironment;
use Tests\TestCase;

class StoreCredentialEnvironmentTest extends TestCase
{
    public function test_exposes_source_and_target_credentials_through_env(): void
    {
        $environment = new StoreCredentialEnvironment;

        $captured = $environment->during($this->access(), function (): array {
            return [
                env('SOURCE_CART_ID'),
                env('SOURCE_STORE_URL'),
                env('SOURCE_ACCOUNT_EMAIL'),
                env('SOURCE_ACCOUNT_TOKEN'),
                env('SOURCE_CONNECTION'),
                env('SOURCE_CART_VERSION'),
                env('SOURCE_VARS'),
                env('SOURCE_VALIDATED'),
                env('TARGET_CART_ID'),
                env('TARGET_STORE_URL'),
                env('TARGET_ACCOUNT_EMAIL'),
                env('TARGET_ACCOUNT_TOKEN'),
                env('TARGET_CONNECTION'),
                env('TARGET_CART_VERSION'),
                env('TARGET_VARS'),
                env('TARGET_VALIDATED'),
            ];
        });

        $this->assertSame([
            'Shopify',
            'https://source.example',
            'source@example.com',
            'source-store-token',
            'file',
            '2.0',
            '{"bridge":"abc"}',
            true,
            'CsvToCart',
            'https://target.example',
            'target@example.com',
            'target-store-token',
            'api',
            '',
            '[]',
            false,
        ], $captured);

        $this->assertNull(env('SOURCE_STORE_URL'));
        $this->assertNull(env('TARGET_STORE_URL'));
        $this->assertNull(env('SOURCE_ACCOUNT_TOKEN'));
        $this->assertNull(env('TARGET_ACCOUNT_TOKEN'));
    }

    public function test_restores_env_when_the_script_throws(): void
    {
        $environment = new StoreCredentialEnvironment;

        try {
            $environment->during($this->access(), function (): void {
                throw new \RuntimeException('script failed');
            });
        } catch (\RuntimeException) {
        }

        $this->assertNull(env('SOURCE_ACCOUNT_TOKEN'));
        $this->assertNull(env('TARGET_ACCOUNT_TOKEN'));
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
