<?php

namespace Tests\Feature;

use App\Jobs\RunScriptExecution;
use App\Models\ScriptExecution;
use App\Models\StoreMigration;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_application_returns_a_successful_response(): void
    {
        StoreMigration::factory()->create([
            'id' => 92831,
        ]);

        $response = $this->get('/migrations/92831');

        $response->assertOk();
        $response->assertSee('Script Executions');
        $response->assertSee('demo-script');
        $response->assertSee('History for this migration');
        $response->assertSee('run-script-modal');
    }

    public function test_run_script_redirects_with_status(): void
    {
        Queue::fake([RunScriptExecution::class]);
        $this->fakeGithubCommitLookup();

        StoreMigration::factory()->create([
            'id' => 92831,
        ]);

        $url = $this->allowedGithubScriptUrl();

        $response = $this->from('/migrations/92831')->post('/migrations/92831/executions/run', [
            'url' => $url,
        ]);

        $response->assertRedirect('/migrations/92831');
        $response->assertSessionHas('status');

        $this->get('/migrations/92831')
            ->assertSee($url)
            ->assertSee('Queued');

        $this->assertDatabaseHas('script_executions', [
            'store_migration_id' => 92831,
            'script' => 'demo-script',
            'status' => 'queued',
            'url' => $url,
            'commit' => self::GITHUB_FULL_SHA,
        ]);

        Queue::assertPushed(RunScriptExecution::class, function (RunScriptExecution $job) use ($url): bool {
            return $job->scriptExecution->store_migration_id === 92831
                && $job->scriptExecution->url === $url
                && $job->scriptExecution->commit === self::GITHUB_FULL_SHA;
        });
    }

    public function test_run_script_requires_a_url(): void
    {
        StoreMigration::factory()->create([
            'id' => 92831,
        ]);

        $response = $this->from('/migrations/92831')->post('/migrations/92831/executions/run');

        $response->assertRedirect('/migrations/92831');
        $response->assertSessionHasErrors('url');
    }

    public function test_run_script_is_tied_to_the_migration(): void
    {
        Queue::fake([RunScriptExecution::class]);
        $this->fakeGithubCommitLookup();

        $migration = StoreMigration::factory()->create([
            'id' => 92831,
        ]);

        ScriptExecution::factory()->for($migration)->create([
            'url' => $this->allowedGithubScriptUrl(path: 'scripts/first.php'),
        ]);

        StoreMigration::factory()->create([
            'id' => 44102,
        ]);

        $url = $this->allowedGithubScriptUrl(path: 'scripts/second.php');

        $this->from('/migrations/44102')->post('/migrations/44102/executions/run', [
            'url' => $url,
        ]);

        $this->assertDatabaseHas('script_executions', [
            'store_migration_id' => 44102,
            'url' => $url,
        ]);

        $this->assertDatabaseMissing('script_executions', [
            'store_migration_id' => 92831,
            'url' => $url,
        ]);
    }

    public function test_run_script_rejects_a_repository_that_is_not_allowed(): void
    {
        StoreMigration::factory()->create([
            'id' => 92831,
        ]);

        $response = $this->from('/migrations/92831')->post('/migrations/92831/executions/run', [
            'url' => 'https://github.com/octocat/Hello-World/blob/'.self::GITHUB_SHORT_SHA.'/README.md',
        ]);

        $response->assertRedirect('/migrations/92831');
        $response->assertSessionHasErrors([
            'url' => 'That repository is not on the allow-list.',
        ]);
    }
}
