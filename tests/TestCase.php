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
}
