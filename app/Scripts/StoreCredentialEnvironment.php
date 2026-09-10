<?php

namespace App\Scripts;

use App\Cart2Cart\Cart2CartStoreAccess;
use App\Cart2Cart\Cart2CartStoreCredentials;
use Illuminate\Support\Env;
use JsonException;

class StoreCredentialEnvironment
{
    public const SOURCE_CART_ID = 'SOURCE_CART_ID';

    public const SOURCE_STORE_URL = 'SOURCE_STORE_URL';

    public const SOURCE_ACCOUNT_EMAIL = 'SOURCE_ACCOUNT_EMAIL';

    public const SOURCE_ACCOUNT_TOKEN = 'SOURCE_ACCOUNT_TOKEN';

    public const SOURCE_CONNECTION = 'SOURCE_CONNECTION';

    public const SOURCE_CART_VERSION = 'SOURCE_CART_VERSION';

    public const SOURCE_VARS = 'SOURCE_VARS';

    public const SOURCE_VALIDATED = 'SOURCE_VALIDATED';

    public const TARGET_CART_ID = 'TARGET_CART_ID';

    public const TARGET_STORE_URL = 'TARGET_STORE_URL';

    public const TARGET_ACCOUNT_EMAIL = 'TARGET_ACCOUNT_EMAIL';

    public const TARGET_ACCOUNT_TOKEN = 'TARGET_ACCOUNT_TOKEN';

    public const TARGET_CONNECTION = 'TARGET_CONNECTION';

    public const TARGET_CART_VERSION = 'TARGET_CART_VERSION';

    public const TARGET_VARS = 'TARGET_VARS';

    public const TARGET_VALIDATED = 'TARGET_VALIDATED';

    /**
     * @var array<string, string|null>
     */
    private array $previous = [];

    /**
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public function during(Cart2CartStoreAccess $access, callable $callback): mixed
    {
        $this->load($access);

        try {
            return $callback();
        } finally {
            $this->restore();
        }
    }

    public function load(Cart2CartStoreAccess $access): void
    {
        $repository = Env::getRepository();

        foreach ($this->variables($access) as $name => $value) {
            $this->previous[$name] = $repository->get($name);
            $repository->set($name, $value);
        }
    }

    public function restore(): void
    {
        $repository = Env::getRepository();

        foreach ($this->previous as $name => $value) {
            if ($value === null) {
                $repository->clear($name);
            } else {
                $repository->set($name, $value);
            }
        }

        $this->previous = [];
    }

    /**
     * @return array<string, string>
     */
    public function variables(Cart2CartStoreAccess $access): array
    {
        return [
            ...$this->prefixed('SOURCE', $access->source),
            ...$this->prefixed('TARGET', $access->target),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function prefixed(string $prefix, Cart2CartStoreCredentials $credentials): array
    {
        return [
            "{$prefix}_CART_ID" => $this->scalar($credentials->cartId),
            "{$prefix}_STORE_URL" => $this->scalar($credentials->url),
            "{$prefix}_ACCOUNT_EMAIL" => $this->scalar($credentials->accountEmail),
            "{$prefix}_ACCOUNT_TOKEN" => $this->scalar($credentials->accountToken),
            "{$prefix}_CONNECTION" => $this->scalar($credentials->connection),
            "{$prefix}_CART_VERSION" => $this->scalar($credentials->cartVersion),
            "{$prefix}_VARS" => $this->scalar($credentials->vars),
            "{$prefix}_VALIDATED" => $this->scalar($credentials->validated),
        ];
    }

    private function scalar(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        try {
            return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (JsonException) {
            return '';
        }
    }
}
