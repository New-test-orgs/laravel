<?php

namespace Tests\Unit\Github;

use App\Github\GithubAllowedRepositories;
use Tests\TestCase;

class GithubAllowedRepositoriesTest extends TestCase
{
    public function test_allows_the_default_repository(): void
    {
        $repositories = new GithubAllowedRepositories;

        $this->assertTrue($repositories->contains(
            GithubAllowedRepositories::OWNER,
            GithubAllowedRepositories::REPO,
        ));
        $this->assertSame(GithubAllowedRepositories::url(), $repositories->primaryUrl());
    }

    public function test_allows_the_new_test_orgs_laravel_repository(): void
    {
        $this->assertTrue((new GithubAllowedRepositories)->contains('New-test-orgs', 'laravel'));
    }

    public function test_rejects_a_repository_that_is_not_the_default(): void
    {
        $this->assertFalse((new GithubAllowedRepositories)->contains('octocat', 'Hello-World'));
    }
}
