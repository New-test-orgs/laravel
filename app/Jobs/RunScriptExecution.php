<?php

namespace App\Jobs;

use App\Github\GithubScriptFetcher;
use App\Github\GithubScriptLoader;
use App\Github\GithubUrlParser;
use App\Models\ScriptExecution;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
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
    ): void {
        $this->scriptExecution->update([
            'status' => 'running',
            'started_at' => now(),
        ]);

        $reference = $parser->parse((string) $this->scriptExecution->url)
            ->withSha((string) $this->scriptExecution->commit);

        $script = $loader->load($fetcher->fetch($reference));
        $script->handle($this->scriptExecution);

        $this->scriptExecution->update([
            'status' => 'completed',
            'processed' => $this->scriptExecution->total,
            'finished_at' => now(),
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        $this->scriptExecution->update([
            'status' => 'failed',
            'finished_at' => now(),
        ]);
    }
}
