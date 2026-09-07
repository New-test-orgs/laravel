<?php

namespace Tests\Feature\Jobs;

use App\Jobs\RunScriptExecution;
use App\Models\ScriptExecution;
use App\Scripts\MigrationScript;
use Illuminate\Support\Sleep;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class RunScriptExecutionTest extends TestCase
{
    public function test_completes_the_demo_script_after_queued_delays(): void
    {
        Sleep::fake();

        $execution = ScriptExecution::factory()->create([
            'status' => 'queued',
            'processed' => 0,
            'total' => 5000,
            'commit' => 'pending',
            'finished_at' => null,
        ]);

        RunScriptExecution::dispatchSync($execution);

        $execution->refresh();

        $this->assertSame('completed', $execution->status);
        $this->assertSame(5000, $execution->processed);
        $this->assertSame('demo', $execution->commit);
        $this->assertNotNull($execution->finished_at);

        Sleep::assertSleptTimes(5);
    }

    public function test_marks_the_execution_as_failed_when_the_script_throws(): void
    {
        $this->mock(MigrationScript::class, function (MockInterface $mock): void {
            $mock->expects('handle')->andThrow(new RuntimeException('demo script failed'));
        });

        $execution = ScriptExecution::factory()->create([
            'status' => 'queued',
        ]);

        try {
            RunScriptExecution::dispatchSync($execution);
        } catch (RuntimeException) {
        }

        $execution->refresh();

        $this->assertSame('failed', $execution->status);
        $this->assertNotNull($execution->finished_at);
    }
}
