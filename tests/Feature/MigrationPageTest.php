<?php

namespace Tests\Feature;

use App\Jobs\RunScriptExecution;
use App\Models\StoreMigration;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MigrationPageTest extends TestCase
{
    public function test_the_migrations_journal_renders_stored_rows(): void
    {
        StoreMigration::factory()->create([
            'id' => 92831,
            'source' => 'Magento 1.9',
            'target' => 'Shopify Plus',
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Migrations');
        $response->assertSee('Magento 1.9');
        $response->assertSee('All migrations');
        $response->assertSee('#92831');
        $response->assertSee('new-migration-modal');
    }

    public function test_unknown_migration_returns_404(): void
    {
        $response = $this->get('/migrations/1');

        $response->assertNotFound();
    }

    public function test_creates_a_temporary_migration_without_platforms(): void
    {
        Queue::fake([RunScriptExecution::class]);
        $this->fakeGithubCommitLookup();

        $url = $this->allowedGithubScriptUrl();

        $response = $this->from('/')->post('/migrations', [
            'id' => 44102,
            'url' => $url,
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHas('status');

        $this->get('/')
            ->assertSee('#44102')
            ->assertSee('Unspecified')
            ->assertSee('demo-script');

        $this->get('/migrations/44102')
            ->assertOk()
            ->assertSee('Script Executions')
            ->assertSee('Unspecified')
            ->assertSee($url);

        $this->assertDatabaseHas('store_migrations', [
            'id' => 44102,
            'source' => 'Unspecified',
            'target' => 'Unspecified',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('script_executions', [
            'store_migration_id' => 44102,
            'script' => 'demo-script',
            'status' => 'queued',
            'url' => $url,
            'commit' => self::GITHUB_FULL_SHA,
        ]);

        Queue::assertPushed(RunScriptExecution::class, function (RunScriptExecution $job) use ($url): bool {
            return $job->scriptExecution->store_migration_id === 44102
                && $job->scriptExecution->url === $url
                && $job->scriptExecution->commit === self::GITHUB_FULL_SHA;
        });
    }

    public function test_creates_a_temporary_migration_with_source_and_target(): void
    {
        Queue::fake([RunScriptExecution::class]);
        $this->fakeGithubCommitLookup();

        $response = $this->from('/')->post('/migrations', [
            'id' => 44102,
            'url' => $this->allowedGithubScriptUrl(),
            'source' => 'OpenCart',
            'target' => 'Shopify',
        ]);

        $response->assertRedirect('/');

        $this->get('/')
            ->assertSee('#44102')
            ->assertSee('OpenCart')
            ->assertSee('Shopify');

        $this->assertDatabaseHas('store_migrations', [
            'id' => 44102,
            'source' => 'OpenCart',
            'target' => 'Shopify',
        ]);
    }

    public function test_rejects_missing_migration_id_and_run_url(): void
    {
        $response = $this->from('/')->post('/migrations');

        $response->assertRedirect('/');
        $response->assertSessionHasErrors(['id', 'url']);
        $response->assertSessionHasErrors([
            'id' => 'The id field is required.',
            'url' => 'The url field is required.',
        ]);
    }

    public function test_rejects_a_duplicate_migration_id(): void
    {
        $this->fakeGithubCommitLookup();

        StoreMigration::factory()->create([
            'id' => 92831,
        ]);

        $response = $this->from('/')->post('/migrations', [
            'id' => 92831,
            'url' => $this->allowedGithubScriptUrl(),
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHasErrors([
            'id' => 'This migration ID is already in the journal.',
        ]);
    }

    public function test_rejects_an_invalid_run_url(): void
    {
        $response = $this->from('/')->post('/migrations', [
            'id' => 44102,
            'url' => 'not-a-url',
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHasErrors(['url']);
    }

    public function test_rejects_unknown_platforms(): void
    {
        $this->fakeGithubCommitLookup();

        $response = $this->from('/')->post('/migrations', [
            'id' => 44102,
            'url' => $this->allowedGithubScriptUrl(),
            'source' => 'NotACart',
            'target' => 'AlsoFake',
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHasErrors(['source', 'target']);
    }
}
