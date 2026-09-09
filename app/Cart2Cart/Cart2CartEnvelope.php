<?php

namespace App\Cart2Cart;

use Illuminate\Http\Client\Response;

final class Cart2CartEnvelope
{
    /**
     * @return array<string, mixed>
     */
    public static function payload(Response $response): array
    {
        $body = $response->json();

        if (! is_array($body) || ! array_key_exists('success', $body)) {
            throw new Cart2CartException(
                'Cart2Cart returned HTTP '.$response->status().' without a JSON envelope. Change C2C_API_BASE_URL.',
                $response->status(),
            );
        }

        if ($body['success'] !== true) {
            $code = isset($body['code']) ? (int) $body['code'] : $response->status();

            throw new Cart2CartException(self::errorMessage($body), $code);
        }

        $payload = $body['payload'] ?? [];

        if (! is_array($payload)) {
            throw new Cart2CartException('Cart2Cart returned an invalid payload.');
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private static function errorMessage(array $body): string
    {
        $error = $body['error'] ?? null;

        if (is_string($error) && $error !== '') {
            return $error;
        }

        if (is_array($error) && is_string($error['message'] ?? null) && $error['message'] !== '') {
            return $error['message'];
        }

        if (is_string($body['message'] ?? null) && $body['message'] !== '') {
            return $body['message'];
        }

        return 'Cart2Cart request failed.';
    }
}
