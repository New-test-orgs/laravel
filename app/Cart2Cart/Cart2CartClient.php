<?php

namespace App\Cart2Cart;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

class Cart2CartClient
{
    public function __construct(private Cart2CartAuthenticator $authenticator) {}

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function migrations(array $query = []): array
    {
        return $this->get('/v1/admin/migrations', $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function migrationProperties(int $migrationId): array
    {
        return $this->get("/v1/admin/migrations/{$migrationId}/properties");
    }

    public function storeAccess(int $migrationId): Cart2CartStoreAccess
    {
        $payload = $this->get("/v1/admin/migrations/{$migrationId}/stores/access");

        if (! isset($payload['source'], $payload['target']) || ! is_array($payload['source']) || ! is_array($payload['target'])) {
            throw new Cart2CartException('Cart2Cart did not return source and target store access.');
        }

        return new Cart2CartStoreAccess(
            source: Cart2CartStoreCredentials::fromPayload($payload['source']),
            target: Cart2CartStoreCredentials::fromPayload($payload['target']),
        );
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private function get(string $url, array $query = []): array
    {
        try {
            return Cart2CartEnvelope::payload($this->send($url, $query));
        } catch (ConnectionException) {
            throw new Cart2CartException('Cart2Cart could not be reached. Try again.');
        } catch (RequestException $exception) {
            if ($exception->response->status() !== 401) {
                return Cart2CartEnvelope::payload($exception->response);
            }

            $this->authenticator->refreshAccessToken();

            try {
                return Cart2CartEnvelope::payload($this->send($url, $query));
            } catch (ConnectionException) {
                throw new Cart2CartException('Cart2Cart could not be reached. Try again.');
            } catch (RequestException $retryException) {
                return Cart2CartEnvelope::payload($retryException->response);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function send(string $url, array $query = []): Response
    {
        return Http::cart2cart($this->authenticator->accessToken())
            ->retry([100, 500, 1000], 0, function (Throwable $exception): bool {
                return $exception instanceof ConnectionException
                    || ($exception instanceof RequestException
                        && $exception->response->status() !== 522
                        && ($exception->response->serverError() || $exception->response->status() === 429));
            })
            ->throw()
            ->get($url, $query);
    }
}
