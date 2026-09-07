<?php

namespace App\Scripts;

use App\Models\ScriptExecution;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;

class DemoMigrationScript implements MigrationScript
{
    public const NAME = 'demo-script';

    public const STEPS = 15;

    public const STEP_SECONDS = 5;

    public function handle(ScriptExecution $execution): void
    {
        $stepSize = (int) ceil($execution->total / self::STEPS);

        Log::info('Migration script started', [
            'execution_id' => $execution->id,
            'total' => $execution->total,
            'steps' => self::STEPS,
        ]);

        for ($step = 1; $step <= self::STEPS; $step++) {
            Sleep::for(self::STEP_SECONDS)->seconds();

            $processed = min($execution->total, $step * $stepSize);

            $execution->update([
                'processed' => $processed,
            ]);

            Log::info('Migration script step completed', [
                'execution_id' => $execution->id,
                'step' => $step,
                'processed' => $processed,
                'total' => $execution->total,
            ]);
        }

        Log::info('Migration script finished', [
            'execution_id' => $execution->id,
            'processed' => $execution->processed,
            'total' => $execution->total,
        ]);
    }
}
