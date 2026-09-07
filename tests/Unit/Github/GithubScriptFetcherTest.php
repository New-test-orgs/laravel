<?php

namespace Tests\Unit\Github;

use App\Github\GithubAllowedRepositories;
use App\Github\GithubObjectKind;
use App\Github\GithubScriptFetcher;
use App\Github\GithubScriptFetchException;
use App\Github\GithubScriptReference;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GithubScriptFetcherTest extends TestCase
{
    public function test_downloads_the_file_for_the_resolved_commit(): void
    {
        Storage::fake('local');
        $this->fakeGithubScriptContents();

        $path = (new GithubScriptFetcher)->fetch($this->blobReference());

        $this->assertSame($this->githubScriptFixture('completes'), file_get_contents($path));
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.github.com/repos/'.GithubAllowedRepositories::slug().'/contents/scripts/demo.php?ref='.self::GITHUB_FULL_SHA);
    }

    public function test_reuses_a_cached_file_instead_of_calling_github(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('scripts/'.self::GITHUB_FULL_SHA.'/scripts/demo.php', $this->githubScriptFixture('completes'));
        Http::preventStrayRequests();

        $path = (new GithubScriptFetcher)->fetch($this->blobReference());

        $this->assertSame($this->githubScriptFixture('completes'), file_get_contents($path));
    }

    public function test_rejects_a_github_path_that_is_not_a_file(): void
    {
        Storage::fake('local');
        $this->fakeGithubScriptContents(payload: [
            'type' => 'dir',
        ]);

        $this->expectException(GithubScriptFetchException::class);
        $this->expectExceptionMessage('The GitHub permalink must point to a file.');

        (new GithubScriptFetcher)->fetch($this->blobReference());
    }

    public function test_rejects_a_file_github_does_not_know(): void
    {
        Storage::fake('local');
        $this->fakeGithubScriptContents(status: 404, payload: ['message' => 'Not Found']);

        $this->expectException(GithubScriptFetchException::class);
        $this->expectExceptionMessage('That file does not exist at the given commit.');

        (new GithubScriptFetcher)->fetch($this->blobReference());
    }

    private function blobReference(): GithubScriptReference
    {
        return new GithubScriptReference(
            owner: GithubAllowedRepositories::OWNER,
            repo: GithubAllowedRepositories::REPO,
            sha: self::GITHUB_FULL_SHA,
            path: 'scripts/demo.php',
            kind: GithubObjectKind::Blob,
            url: $this->allowedGithubScriptUrl('blob', self::GITHUB_FULL_SHA),
        );
    }
}
