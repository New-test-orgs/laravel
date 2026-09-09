<?php

use App\Models\ScriptExecution;
use App\Scripts\MigrationScript;
use Illuminate\Support\Facades\Log;

return new class implements MigrationScript
{
    public function handle(ScriptExecution $execution): void
    {
        $credentials = $execution->storeCredentials();

        Log::info('Store credentials for script execution', [
            'store_side' => $execution->store_side->value,
            ...$credentials->toArray(),
        ]);
    }
};
