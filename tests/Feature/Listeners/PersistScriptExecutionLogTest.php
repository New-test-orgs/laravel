<?php

namespace Tests\Feature\Listeners;

use App\Models\ScriptExecution;
use App\Models\ScriptExecutionLog;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class PersistScriptExecutionLogTest extends TestCase
{
    public function test_persists_a_log_bound_to_the_execution_and_migration(): void
    {
        $execution = ScriptExecution::factory()->create();

        Log::info('step done', [
            'script_execution_id' => $execution->id,
            'store_migration_id' => $execution->store_migration_id,
            'step' => 3,
        ]);

        $this->assertDatabaseHas('script_execution_logs', [
            'script_execution_id' => $execution->id,
            'store_migration_id' => $execution->store_migration_id,
            'level' => 'info',
            'message' => 'step done',
        ]);

        $log = ScriptExecutionLog::query()->sole();

        $this->assertSame(3, $log->context['step']);
    }

    public function test_does_not_persist_logs_without_execution_and_migration_bindings(): void
    {
        $execution = ScriptExecution::factory()->create();

        Log::info('unrelated');
        Log::info('only run', ['script_execution_id' => $execution->id]);
        Log::info('only migration', ['store_migration_id' => $execution->store_migration_id]);

        $this->assertDatabaseCount('script_execution_logs', 0);
    }
}
