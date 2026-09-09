<?php

namespace App\Jobs;

use App\Cart2Cart\Cart2CartClient;
use App\Github\GithubScriptFetcher;
use App\Github\GithubScriptLoader;
use App\Github\GithubUrlParser;
use App\Models\ScriptExecution;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Support\Facades\Log;
use Throwable;

#[Tries(1)]
#[Timeout(1800)]
class RunScriptExecution implements ShouldQueue
{
    use Queueable;

    public function __construct(public ScriptExecution $scriptExecution) {}

    public function handle(
        GithubUrlParser $parser,
        GithubScriptFetcher $fetcher,
        GithubScriptLoader $loader,
        Cart2CartClient $cart2Cart,
    ): void {
        Log::withContext([
            'script_execution_id' => $this->scriptExecution->id,
            'store_migration_id' => $this->scriptExecution->store_migration_id,
            'store_side' => $this->scriptExecution->store_side->value,
        ]);

        $this->scriptExecution->update([
            'status' => 'running',
            'started_at' => now(),
        ]);

        Log::info('Script execution started', [
            'url' => $this->scriptExecution->url,
            'commit' => $this->scriptExecution->commit,
        ]);

        $reference = $parser->parse((string) $this->scriptExecution->url)
            ->withSha((string) $this->scriptExecution->commit);

        $this->scriptExecution->setStoreAccess(
            $cart2Cart->storeAccess($this->scriptExecution->store_migration_id),
        );

        $script = $loader->load($fetcher->fetch($reference));
        $script->handle($this->scriptExecution);

        $this->scriptExecution->update([
            'status' => 'completed',
            'processed' => $this->scriptExecution->total,
            'finished_at' => now(),
        ]);

        Log::info('Script execution completed');
    }

    public function failed(?Throwable $exception): void
    {
        $this->scriptExecution->update([
            'status' => 'failed',
            'finished_at' => now(),
        ]);

        Log::error('Script execution failed', [
            'script_execution_id' => $this->scriptExecution->id,
            'store_migration_id' => $this->scriptExecution->store_migration_id,
            'exception' => $exception,
        ]);
    }
}
