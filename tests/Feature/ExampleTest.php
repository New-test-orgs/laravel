<?php

namespace Tests\Feature;

use App\Jobs\RunScriptExecution;
use App\Models\ScriptExecution;
use App\Models\ScriptExecutionLog;
use App\Models\StoreMigration;
use App\StoreSide;
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
        $response->assertSee('Run script');
        $response->assertSee('History for this migration');
        $response->assertSee('run-script-modal');
        $response->assertSee('name="store_side"', false);
        $response->assertSee('value="source"', false);
        $response->assertSee('value="target"', false);
        $response->assertSee('Magento 1.9');
        $response->assertSee('Shopify');
    }

    public function test_execution_page_keeps_runner_logs_out_of_the_initial_html(): void
    {
        $migration = StoreMigration::factory()->create([
            'id' => 92831,
        ]);
        $execution = ScriptExecution::factory()->for($migration)->create([
            'script' => 'demo.php',
        ]);
        ScriptExecutionLog::factory()->for($execution)->create([
            'message' => 'Migration script step completed',
        ]);

        $this->get('/migrations/92831')
            ->assertOk()
            ->assertSee('data-run-logs', false)
            ->assertSee(route('executions.logs', [$migration, $execution], false), false)
            ->assertDontSee('Migration script step completed');
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
            'store_side' => 'source',
        ]);

        $response->assertRedirect('/migrations/92831');
        $response->assertSessionHas('status');

        $this->get('/migrations/92831')
            ->assertSee($url)
            ->assertSee('Queued')
            ->assertSeeInOrder(['History for this migration', 'demo.php', 'Source', 'Magento 1.9']);

        $this->assertDatabaseHas('script_executions', [
            'store_migration_id' => 92831,
            'script' => 'demo.php',
            'status' => 'queued',
            'url' => $url,
            'commit' => self::GITHUB_FULL_SHA,
            'store_side' => 'source',
        ]);

        Queue::assertPushed(RunScriptExecution::class, function (RunScriptExecution $job) use ($url): bool {
            return $job->scriptExecution->store_migration_id === 92831
                && $job->scriptExecution->url === $url
                && $job->scriptExecution->commit === self::GITHUB_FULL_SHA
                && $job->scriptExecution->store_side === StoreSide::Source;
        });
    }

    public function test_run_script_requires_a_url(): void
    {
        StoreMigration::factory()->create([
            'id' => 92831,
        ]);

        $response = $this->from('/migrations/92831')->post('/migrations/92831/executions/run', [
            'store_side' => 'source',
        ]);

        $response->assertRedirect('/migrations/92831');
        $response->assertSessionHasErrors('url');
    }

    public function test_run_script_requires_a_store_side(): void
    {
        $this->fakeGithubCommitLookup();

        StoreMigration::factory()->create([
            'id' => 92831,
        ]);

        $response = $this->from('/migrations/92831')->post('/migrations/92831/executions/run', [
            'url' => $this->allowedGithubScriptUrl(),
        ]);

        $response->assertRedirect('/migrations/92831');
        $response->assertSessionHasErrors([
            'store_side' => 'The store side field is required.',
        ]);
        $this->assertDatabaseMissing('script_executions', [
            'store_migration_id' => 92831,
        ]);
    }

    public function test_run_script_rejects_an_invalid_store_side(): void
    {
        $this->fakeGithubCommitLookup();

        StoreMigration::factory()->create([
            'id' => 92831,
        ]);

        $response = $this->from('/migrations/92831')->post('/migrations/92831/executions/run', [
            'url' => $this->allowedGithubScriptUrl(),
            'store_side' => 'both',
        ]);

        $response->assertRedirect('/migrations/92831');
        $response->assertSessionHasErrors([
            'store_side' => 'The selected store side is invalid.',
        ]);
        $this->assertDatabaseMissing('script_executions', [
            'store_migration_id' => 92831,
        ]);
    }

    public function test_execution_list_shows_the_selected_store_side(): void
    {
        $migration = StoreMigration::factory()->create([
            'id' => 92831,
            'source' => 'Magento 1.9',
            'target' => 'Shopify Plus',
        ]);
        ScriptExecution::factory()->for($migration)->create([
            'script' => 'demo.php',
            'store_side' => StoreSide::Target,
        ]);

        $this->get('/migrations/92831')
            ->assertOk()
            ->assertSeeInOrder(['History for this migration', 'demo.php', 'Target', 'Shopify Plus']);
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
            'store_side' => 'target',
        ]);

        $this->assertDatabaseHas('script_executions', [
            'store_migration_id' => 44102,
            'url' => $url,
            'store_side' => 'target',
        ]);

        $this->assertDatabaseMissing('script_executions', [
            'store_migration_id' => 92831,
            'url' => $url,
        ]);
    }

    public function test_run_script_resolves_a_branch_url_to_the_commit(): void
    {
        Queue::fake([RunScriptExecution::class]);
        $this->fakeGithubCommitLookup();

        StoreMigration::factory()->create([
            'id' => 92831,
        ]);

        $url = 'https://github.com/New-test-orgs/laravel/blob/main/scripts/demo.php';

        $this->from('/migrations/92831')->post('/migrations/92831/executions/run', [
            'url' => $url,
            'store_side' => 'source',
        ])->assertRedirect('/migrations/92831');

        $this->assertDatabaseHas('script_executions', [
            'store_migration_id' => 92831,
            'script' => 'demo.php',
            'status' => 'queued',
            'url' => $url,
            'commit' => self::GITHUB_FULL_SHA,
        ]);
    }

    public function test_run_script_rejects_a_repository_that_is_not_allowed(): void
    {
        StoreMigration::factory()->create([
            'id' => 92831,
        ]);

        $response = $this->from('/migrations/92831')->post('/migrations/92831/executions/run', [
            'url' => 'https://github.com/octocat/Hello-World/blob/'.self::GITHUB_SHORT_SHA.'/README.md',
            'store_side' => 'source',
        ]);

        $response->assertRedirect('/migrations/92831');
        $response->assertSessionHasErrors([
            'url' => 'That repository is not on the allow-list.',
        ]);
    }
}
