<?php

namespace Tests\Unit\Github;

use App\Github\GithubScriptFetchException;
use App\Github\GithubScriptLoader;
use App\Scripts\MigrationScript;
use Tests\TestCase;

class GithubScriptLoaderTest extends TestCase
{
    public function test_loads_a_migration_script_from_a_php_file(): void
    {
        $script = (new GithubScriptLoader)->load(base_path('tests/Fixtures/github-scripts/completes.php'));

        $this->assertInstanceOf(MigrationScript::class, $script);
    }

    public function test_rejects_a_file_that_does_not_return_a_migration_script(): void
    {
        $this->expectException(GithubScriptFetchException::class);
        $this->expectExceptionMessage('The GitHub file must return a MigrationScript instance.');

        (new GithubScriptLoader)->load(base_path('tests/Fixtures/github-scripts/invalid.php'));
    }
}