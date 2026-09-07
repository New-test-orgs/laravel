<?php

namespace App\Scripts;

use App\Models\ScriptExecution;
use Illuminate\Support\Sleep;

class DemoMigrationScript implements MigrationScript
{
    public const NAME = 'demo-script';

    public const STEPS = 5;

    public const STEP_SECONDS = 15;

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
}
