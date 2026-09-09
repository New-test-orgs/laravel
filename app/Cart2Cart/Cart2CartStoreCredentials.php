<?php

namespace App\Cart2Cart;

final readonly class Cart2CartStoreCredentials
{
    /**
     * @param  array<string, mixed>  $vars
     */
    public function __construct(
        public mixed $cartId,
        public ?string $url,
        public ?string $accountEmail,
        public ?string $accountToken,
        public ?string $connection,
        public mixed $cartVersion,
        public array $vars,
        public ?bool $validated,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromPayload(array $payload): self
    {
        $vars = $payload['vars'] ?? [];

        return new self(
            cartId: $payload['cart_id'] ?? null,
            url: self::nullableString($payload['url'] ?? null),
            accountEmail: self::nullableString($payload['account_email'] ?? null),
            accountToken: self::nullableString($payload['account_token'] ?? null),
            connection: self::nullableString($payload['connection'] ?? null),
            cartVersion: $payload['cart_version'] ?? null,
            vars: is_array($vars) ? $vars : [],
            validated: is_bool($payload['validated'] ?? null) ? $payload['validated'] : null,
        );
    }

    public function cartLabel(): string
    {
        if (is_string($this->cartId)) {
            $label = trim($this->cartId);

            return $label !== '' ? $label : 'Unspecified';
        }

        if (is_int($this->cartId) || is_float($this->cartId)) {
            return (string) $this->cartId;
        }

        return 'Unspecified';
    }

    /**
     * @return array{
     *     cart_id: mixed,
     *     url: ?string,
     *     account_email: ?string,
     *     account_token: ?string,
     *     connection: ?string,
     *     cart_version: mixed,
     *     vars: array<string, mixed>,
     *     validated: ?bool
     * }
     */
    public function toArray(): array
    {
        return [
            'cart_id' => $this->cartId,
            'url' => $this->url,
            'account_email' => $this->accountEmail,
            'account_token' => $this->accountToken,
            'connection' => $this->connection,
            'cart_version' => $this->cartVersion,
            'vars' => $this->vars,
            'validated' => $this->validated,
        ];
    }

    private static function nullableString(mixed $value): ?string
    {
        return is_string($value) ? $value : null;
    }
}
