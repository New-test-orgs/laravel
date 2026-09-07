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
}
