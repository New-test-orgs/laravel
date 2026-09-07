<?php

use App\Models\ScriptExecution;
use App\Scripts\MigrationScript;

return new class implements MigrationScript
{
    public function handle(ScriptExecution $execution): void
    {
        $execution->update([
            'processed' => $execution->total,
        ]);
    }
};
