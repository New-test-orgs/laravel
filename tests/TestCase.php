<?php

namespace Tests;

use App\Github\GithubAllowedRepositories;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    use LazilyRefreshDatabase;

    protected const GITHUB_FULL_SHA = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

    protected const GITHUB_SHORT_SHA = 'aaaaaaa';

    protected function allowedGithubScriptUrl(
        string $kind = 'blob',
        string $sha = self::GITHUB_SHORT_SHA,
        string $path = 'scripts/demo.php',
    ): string {
        $suffix = $path === '' ? '' : '/'.$path;

        return GithubAllowedRepositories::url()."/{$kind}/{$sha}{$suffix}";
    }

    protected function fakeGithubCommitLookup(string $fullSha = self::GITHUB_FULL_SHA, int $status = 200): void
    {
        Http::preventStrayRequests();

        Http::fake([
            'https://api.github.com/repos/*/commits/*' => Http::response(
                $status === 200 ? ['sha' => $fullSha] : ['message' => 'Not Found'],
                $status,
            ),
        ]);
    }

    protected function githubScriptFixture(string $name): string
    {
        $contents = file_get_contents(base_path('tests/Fixtures/github-scripts/'.$name.'.php'));

        $this->assertIsString($contents);

        return $contents;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function fakeGithubScriptContents(
        string $fixture = 'completes',
        string $path = 'scripts/demo.php',
        int $status = 200,
        ?array $payload = null,
        ?string $source = null,
    ): void {
        Http::preventStrayRequests();

        $source ??= $this->githubScriptFixture($fixture);

        Http::fake([
            'https://api.github.com/repos/*/contents/*' => Http::response(
                $payload ?? [
                    'type' => 'file',
                    'encoding' => 'base64',
                    'size' => strlen($source),
                    'path' => $path,
                    'content' => base64_encode($source),
                ],
                $status,
            ),
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $error
     */
    protected function fakeCart2CartStoreAccess(
        int $migrationId,
        mixed $sourceCartId = 'OpenCart',
        mixed $targetCartId = 'Shopify',
        ?array $error = null,
        int $status = 200,
    ): void {
        config([
            'services.cart2cart.email' => 'c2c@example.com',
            'services.cart2cart.password' => 'secret',
        ]);

        $baseUrl = rtrim((string) config('services.cart2cart.base_url'), '/');

        Http::preventStrayRequests();
        Http::fake([
            $baseUrl.'/v1/auth/login' => [
                'success' => true,
                'payload' => ['access_token' => '12|c2c-access'],
                'code' => 200,
            ],
            $baseUrl.'/v1/admin/migrations/'.$migrationId.'/stores/access' => Http::response(
                $error ?? [
                    'success' => true,
                    'payload' => [
                        'source' => [
                            'cart_id' => $sourceCartId,
                            'url' => 'https://source.example',
                            'account_email' => 'source@example.com',
                            'account_token' => 'source-store-token',
                            'connection' => 'file',
                            'cart_version' => '2.0',
                            'vars' => ['bridge' => 'abc'],
                            'validated' => true,
                        ],
                        'target' => [
                            'cart_id' => $targetCartId,
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
                $status,
            ),
        ]);
    }
}
