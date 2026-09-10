<?php

namespace Tests\Feature\Jobs;

use App\Cart2Cart\Cart2CartException;
use App\Jobs\RunScriptExecution;
use App\Models\ScriptExecution;
use App\Models\ScriptExecutionLog;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;
use Tests\TestCase;

class RunScriptExecutionTest extends TestCase
{
    public function test_downloads_and_completes_the_github_script(): void
    {
        Storage::fake('local');

        $execution = ScriptExecution::factory()->create([
            'status' => 'queued',
            'processed' => 0,
            'total' => 5000,
            'commit' => self::GITHUB_FULL_SHA,
            'finished_at' => null,
        ]);

        $this->fakeGithubScriptContents();
        $this->fakeCart2CartStoreAccess($execution->store_migration_id);

        RunScriptExecution::dispatchSync($execution);

        $execution->refresh();

        $this->assertSame('completed', $execution->status);
        $this->assertSame(5000, $execution->processed);
        $this->assertSame(self::GITHUB_FULL_SHA, $execution->commit);
        $this->assertNotNull($execution->finished_at);

        $this->assertDatabaseHas('script_execution_logs', [
            'script_execution_id' => $execution->id,
            'store_migration_id' => $execution->store_migration_id,
            'message' => 'Script execution started',
        ]);
        $this->assertDatabaseHas('script_execution_logs', [
            'script_execution_id' => $execution->id,
            'store_migration_id' => $execution->store_migration_id,
            'message' => 'Script execution completed',
        ]);
        Http::assertSent(fn (Request $request): bool => str_ends_with(
            $request->url(),
            '/v1/admin/migrations/'.$execution->store_migration_id.'/stores/access',
        ));
    }

    public function test_runs_the_repository_demo_script_with_queued_delays(): void
    {
        Sleep::fake();
        Storage::fake('local');

        $source = file_get_contents(base_path('scripts/demo.php'));
        $this->assertIsString($source);

        $execution = ScriptExecution::factory()->create([
            'status' => 'queued',
            'processed' => 0,
            'total' => 5000,
        ]);

        $this->fakeGithubScriptContents(source: $source);
        $this->fakeCart2CartStoreAccess($execution->store_migration_id);

        RunScriptExecution::dispatchSync($execution);

        $execution->refresh();

        $this->assertSame('completed', $execution->status);
        $this->assertSame(5000, $execution->processed);
        Sleep::assertSleptTimes(15);
    }

    public function test_marks_the_execution_as_failed_when_the_script_throws(): void
    {
        Storage::fake('local');

        $execution = ScriptExecution::factory()->create([
            'status' => 'queued',
        ]);

        $this->fakeGithubScriptContents('fails');
        $this->fakeCart2CartStoreAccess($execution->store_migration_id);

        try {
            RunScriptExecution::dispatchSync($execution);
        } catch (\RuntimeException) {
        }

        $execution->refresh();

        $this->assertSame('failed', $execution->status);
        $this->assertNotNull($execution->finished_at);
        $this->assertDatabaseHas('script_execution_logs', [
            'script_execution_id' => $execution->id,
            'store_migration_id' => $execution->store_migration_id,
            'level' => 'error',
            'message' => 'Script execution failed',
        ]);
    }

    public function test_dumps_source_and_target_store_credentials_from_env_in_script_logs(): void
    {
        Storage::fake('local');

        $source = file_get_contents(base_path('scripts/dump-store-credentials.php'));
        $this->assertIsString($source);

        $execution = ScriptExecution::factory()->create([
            'status' => 'queued',
            'processed' => 0,
            'total' => 5000,
        ]);

        $this->fakeGithubScriptContents(source: $source);
        $this->fakeCart2CartStoreAccess($execution->store_migration_id);

        RunScriptExecution::dispatchSync($execution);

        $execution->refresh();

        $this->assertSame('completed', $execution->status);

        $log = ScriptExecutionLog::query()
            ->where('script_execution_id', $execution->id)
            ->where('message', 'Store credentials for script execution')
            ->sole();

        $this->assertSame('OpenCart', $log->context['SOURCE_CART_ID']);
        $this->assertSame('https://source.example', $log->context['SOURCE_STORE_URL']);
        $this->assertSame('source@example.com', $log->context['SOURCE_ACCOUNT_EMAIL']);
        $this->assertSame('source-store-token', $log->context['SOURCE_ACCOUNT_TOKEN']);
        $this->assertSame('file', $log->context['SOURCE_CONNECTION']);
        $this->assertSame('2.0', $log->context['SOURCE_CART_VERSION']);
        $this->assertSame('{"bridge":"abc"}', $log->context['SOURCE_VARS']);
        $this->assertTrue($log->context['SOURCE_VALIDATED']);
        $this->assertSame('Shopify', $log->context['TARGET_CART_ID']);
        $this->assertSame('https://target.example', $log->context['TARGET_STORE_URL']);
        $this->assertSame('target@example.com', $log->context['TARGET_ACCOUNT_EMAIL']);
        $this->assertSame('target-store-token', $log->context['TARGET_ACCOUNT_TOKEN']);
        $this->assertSame('api', $log->context['TARGET_CONNECTION']);
        $this->assertSame('', $log->context['TARGET_CART_VERSION']);
        $this->assertSame('[]', $log->context['TARGET_VARS']);
        $this->assertFalse($log->context['TARGET_VALIDATED']);
        $this->assertNull(env('SOURCE_ACCOUNT_TOKEN'));
        $this->assertNull(env('TARGET_ACCOUNT_TOKEN'));
    }

    public function test_marks_the_execution_as_failed_when_cart2cart_store_access_fails(): void
    {
        Storage::fake('local');

        $execution = ScriptExecution::factory()->create([
            'status' => 'queued',
        ]);

        $this->fakeGithubScriptContents();
        $this->fakeCart2CartStoreAccess(
            migrationId: $execution->store_migration_id,
            error: [
                'success' => false,
                'error' => ['message' => 'Migration not found'],
                'code' => 404,
            ],
            status: 404,
        );

        try {
            RunScriptExecution::dispatchSync($execution);
        } catch (Cart2CartException) {
        }

        $execution->refresh();

        $this->assertSame('failed', $execution->status);
        $this->assertNotNull($execution->finished_at);
        $this->assertDatabaseHas('script_execution_logs', [
            'script_execution_id' => $execution->id,
            'store_migration_id' => $execution->store_migration_id,
            'level' => 'error',
            'message' => 'Script execution failed',
        ]);
    }
}
