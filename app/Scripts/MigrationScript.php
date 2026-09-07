<?php

namespace App\Scripts;

use App\Models\ScriptExecution;

interface MigrationScript
{
    public function handle(ScriptExecution $execution): void;
}
