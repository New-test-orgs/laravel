<?php

namespace Tests\Feature\Jobs;

use App\Jobs\RunScriptExecution;
use App\Models\ScriptExecution;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RunScriptExecutionTest extends TestCase
{
    public function test_downloads_and_completes_the_github_script(): void
    {
        Storage::fake('local');
        $this->fakeGithubScriptContents();

        $execution = ScriptExecution::factory()->create([
            'status' => 'queued',
            'processed' => 0,
            'total' => 5000,
            'commit' => self::GITHUB_FULL_SHA,
            'finished_at' => null,
        ]);

        RunScriptExecution::dispatchSync($execution);

        $execution->refresh();

        $this->assertSame('completed', $execution->status);
        $this->assertSame(5000, $execution->processed);
        $this->assertSame(self::GITHUB_FULL_SHA, $execution->commit);
        $this->assertNotNull($execution->finished_at);
    }

    public function test_marks_the_execution_as_failed_when_the_script_throws(): void
    {
        Storage::fake('local');
        $this->fakeGithubScriptContents('fails');

        $execution = ScriptExecution::factory()->create([
            'status' => 'queued',
        ]);

        try {
            RunScriptExecution::dispatchSync($execution);
        } catch (\RuntimeException) {
        }

        $execution->refresh();

        $this->assertSame('failed', $execution->status);
        $this->assertNotNull($execution->finished_at);
    }
}
