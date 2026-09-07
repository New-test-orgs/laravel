<?php

namespace Tests\Unit\Github;

use App\Github\GithubAllowedRepositories;
use App\Github\GithubObjectKind;
use App\Github\GithubUrlParser;
use App\Github\InvalidGithubScriptUrlException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GithubUrlParserTest extends TestCase
{
    private GithubUrlParser $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = $this->app->make(GithubUrlParser::class);
    }

    public function test_parses_a_blob_permalink_with_a_short_sha(): void
    {
        $reference = $this->parser->parse($this->allowedGithubScriptUrl());

        $this->assertSame(GithubAllowedRepositories::OWNER, $reference->owner);
        $this->assertSame(GithubAllowedRepositories::REPO, $reference->repo);
        $this->assertSame(self::GITHUB_SHORT_SHA, $reference->sha);
        $this->assertSame('scripts/demo.php', $reference->path);
        $this->assertSame('demo.php', $reference->filename());
        $this->assertSame(GithubObjectKind::Blob, $reference->kind);
    }

    public function test_ignores_www_and_query_string(): void
    {
        $reference = $this->parser->parse(
            'https://www.github.com/'.GithubAllowedRepositories::slug().'/blob/'.self::GITHUB_SHORT_SHA.'/scripts/demo.php?plain=1',
        );

        $this->assertSame('scripts/demo.php', $reference->path);
        $this->assertSame(GithubObjectKind::Blob, $reference->kind);
    }

    public function test_parses_a_blob_permalink_from_new_test_orgs_laravel(): void
    {
        $reference = $this->parser->parse(
            'https://github.com/New-test-orgs/laravel/blob/'.self::GITHUB_SHORT_SHA.'/scripts/demo.php',
        );

        $this->assertSame('New-test-orgs', $reference->owner);
        $this->assertSame('laravel', $reference->repo);
        $this->assertSame('scripts/demo.php', $reference->path);
    }

    #[DataProvider('invalidUrls')]
    public function test_rejects_urls_that_are_not_allowed_commit_permalinks(string $url, string $message): void
    {
        $this->expectException(InvalidGithubScriptUrlException::class);
        $this->expectExceptionMessage($message);

        $this->parser->parse($url);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function invalidUrls(): array
    {
        return [
            'shop url' => [
                'https://shop.example.com/products/old-url-key',
                'The URL must point to github.com.',
            ],
            'branch name' => [
                GithubAllowedRepositories::url().'/blob/main/scripts/demo.php',
                'The URL must include a commit SHA, not a branch or tag name.',
            ],
            'other repository' => [
                'https://github.com/octocat/Hello-World/blob/'.self::GITHUB_SHORT_SHA.'/README.md',
                'That repository is not on the allow-list.',
            ],
            'commit page' => [
                GithubAllowedRepositories::url().'/commit/'.self::GITHUB_SHORT_SHA,
                'The URL must be a GitHub file permalink.',
            ],
            'tree folder' => [
                GithubAllowedRepositories::url().'/tree/'.self::GITHUB_SHORT_SHA.'/scripts',
                'Paste a GitHub file permalink, not a folder.',
            ],
            'blob without path' => [
                GithubAllowedRepositories::url().'/blob/'.self::GITHUB_SHORT_SHA,
                'A file permalink must include a path after the commit SHA.',
            ],
            'path traversal' => [
                GithubAllowedRepositories::url().'/blob/'.self::GITHUB_SHORT_SHA.'/../secret.php',
                'The GitHub path is not valid.',
            ],
        ];
    }
}
