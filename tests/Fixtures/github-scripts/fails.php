<?php

use App\Models\ScriptExecution;
use App\Scripts\MigrationScript;

return new class implements MigrationScript
{
    public function handle(ScriptExecution $execution): void
    {
        throw new RuntimeException('remote script failed');
    }
};
