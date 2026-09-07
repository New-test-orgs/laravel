<?php

namespace Tests\Unit\Github;

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

        $this->parser = new GithubUrlParser;
    }

    public function test_parses_a_blob_permalink_with_a_short_sha(): void
    {
        $reference = $this->parser->parse($this->allowedGithubScriptUrl());

        $this->assertSame('cart2cart', $reference->owner);
        $this->assertSame('cart2cart-migration-scripts', $reference->repo);
        $this->assertSame(self::GITHUB_SHORT_SHA, $reference->sha);
        $this->assertSame('scripts/demo.php', $reference->path);
        $this->assertSame(GithubObjectKind::Blob, $reference->kind);
    }

    public function test_parses_a_tree_permalink_for_a_folder(): void
    {
        $url = $this->allowedGithubScriptUrl('tree', self::GITHUB_FULL_SHA, 'scripts/magento');

        $reference = $this->parser->parse($url);

        $this->assertSame(GithubObjectKind::Tree, $reference->kind);
        $this->assertSame(self::GITHUB_FULL_SHA, $reference->sha);
        $this->assertSame('scripts/magento', $reference->path);
    }

    public function test_parses_a_tree_permalink_for_the_repository_root(): void
    {
        $reference = $this->parser->parse($this->allowedGithubScriptUrl('tree', self::GITHUB_SHORT_SHA, ''));

        $this->assertSame(GithubObjectKind::Tree, $reference->kind);
        $this->assertSame('', $reference->path);
    }

    public function test_ignores_www_and_query_string(): void
    {
        $reference = $this->parser->parse(
            'https://www.github.com/cart2cart/cart2cart-migration-scripts/blob/'.self::GITHUB_SHORT_SHA.'/scripts/demo.php?plain=1',
        );

        $this->assertSame('scripts/demo.php', $reference->path);
        $this->assertSame(GithubObjectKind::Blob, $reference->kind);
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
                'https://github.com/cart2cart/cart2cart-migration-scripts/blob/main/scripts/demo.php',
                'The URL must include a commit SHA, not a branch or tag name.',
            ],
            'other repository' => [
                'https://github.com/octocat/Hello-World/blob/'.self::GITHUB_SHORT_SHA.'/README.md',
                'That repository is not on the allow-list.',
            ],
            'commit page' => [
                'https://github.com/cart2cart/cart2cart-migration-scripts/commit/'.self::GITHUB_SHORT_SHA,
                'The URL must be a GitHub blob or tree permalink.',
            ],
            'blob without path' => [
                'https://github.com/cart2cart/cart2cart-migration-scripts/blob/'.self::GITHUB_SHORT_SHA,
                'A file permalink must include a path after the commit SHA.',
            ],
            'path traversal' => [
                'https://github.com/cart2cart/cart2cart-migration-scripts/blob/'.self::GITHUB_SHORT_SHA.'/../secret.php',
                'The GitHub path is not valid.',
            ],
        ];
    }
}
