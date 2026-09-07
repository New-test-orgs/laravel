<?php

use App\Models\ScriptExecution;
use App\Scripts\MigrationScript;
use Illuminate\Support\Sleep;

return new class implements MigrationScript
{
    private const STEPS = 15;

    private const STEP_SECONDS = 5;

    public function handle(ScriptExecution $execution): void
    {
        $stepSize = (int) ceil($execution->total / self::STEPS);

        for ($step = 1; $step <= self::STEPS; $step++) {
            Sleep::for(self::STEP_SECONDS)->seconds();

            $execution->update([
                'processed' => min($execution->total, $step * $stepSize),
            ]);
        }
    }
};
