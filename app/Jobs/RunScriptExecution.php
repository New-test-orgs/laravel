<?php

namespace App\Jobs;

use App\Models\ScriptExecution;
use App\Scripts\MigrationScript;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Throwable;

#[Tries(1)]
#[Timeout(120)]
class RunScriptExecution implements ShouldQueue
{
    use Queueable;

    public function __construct(public ScriptExecution $scriptExecution) {}

    public function handle(MigrationScript $script): void
    {
        $this->scriptExecution->update([
            'status' => 'running',
            'started_at' => now(),
        ]);

        $script->handle($this->scriptExecution);

        $this->scriptExecution->update([
            'status' => 'completed',
            'processed' => $this->scriptExecution->total,
            'commit' => 'demo',
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
