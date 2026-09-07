<?php

namespace Tests\Unit\Github;

use App\Github\GithubAllowedRepositories;
use App\Github\GithubCommitResolver;
use App\Github\GithubObjectKind;
use App\Github\GithubScriptReference;
use App\Github\InvalidGithubScriptUrlException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GithubCommitResolverTest extends TestCase
{
    public function test_resolves_a_short_sha_to_the_full_commit(): void
    {
        $this->fakeGithubCommitLookup();

        $resolved = (new GithubCommitResolver)->resolve($this->shortReference());

        $this->assertSame(self::GITHUB_FULL_SHA, $resolved->sha);
        $this->assertSame(GithubObjectKind::Blob, $resolved->kind);
        $this->assertSame('scripts/demo.php', $resolved->path);

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.github.com/repos/'.GithubAllowedRepositories::slug().'/commits/'.self::GITHUB_SHORT_SHA);
    }

    public function test_rejects_a_commit_that_github_does_not_know(): void
    {
        $this->fakeGithubCommitLookup(status: 404);

        $this->expectException(InvalidGithubScriptUrlException::class);
        $this->expectExceptionMessage('That commit does not exist in the allowed repository.');

        (new GithubCommitResolver)->resolve($this->shortReference());
    }

    private function shortReference(): GithubScriptReference
    {
        return new GithubScriptReference(
            owner: GithubAllowedRepositories::OWNER,
            repo: GithubAllowedRepositories::REPO,
            sha: self::GITHUB_SHORT_SHA,
            path: 'scripts/demo.php',
            kind: GithubObjectKind::Blob,
            url: $this->allowedGithubScriptUrl(),
        );
    }
}
