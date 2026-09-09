<?php

namespace Tests\Feature\Jobs;

use App\Cart2Cart\Cart2CartException;
use App\Jobs\RunScriptExecution;
use App\Models\ScriptExecution;
use App\Models\ScriptExecutionLog;
use App\StoreSide;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;
use PHPUnit\Framework\Attributes\DataProvider;
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

    /**
     * @return array<string, array{0: StoreSide, 1: string}>
     */
    public static function storeSides(): array
    {
        return [
            'source' => [StoreSide::Source, 'source-store-token'],
            'target' => [StoreSide::Target, 'target-store-token'],
        ];
    }

    #[DataProvider('storeSides')]
    public function test_dumps_store_credentials_from_cart2cart_in_script_logs(StoreSide $storeSide, string $accountToken): void
    {
        Storage::fake('local');

        $source = file_get_contents(base_path('scripts/dump-store-credentials.php'));
        $this->assertIsString($source);

        $execution = ScriptExecution::factory()->create([
            'status' => 'queued',
            'store_side' => $storeSide,
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

        $this->assertSame($storeSide->value, $log->context['store_side']);
        $this->assertSame($accountToken, $log->context['account_token']);
        $this->assertSame(
            $storeSide === StoreSide::Source ? 'https://source.example' : 'https://target.example',
            $log->context['url'],
        );
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
