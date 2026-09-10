<?php

use App\Models\ScriptExecution;
use App\Scripts\MigrationScript;
use Illuminate\Support\Facades\Log;

return new class implements MigrationScript
{
    public function handle(ScriptExecution $execution): void
    {
        Log::info('Store credentials for script execution', [
            'SOURCE_CART_ID' => env('SOURCE_CART_ID'),
            'SOURCE_STORE_URL' => env('SOURCE_STORE_URL'),
            'SOURCE_ACCOUNT_EMAIL' => env('SOURCE_ACCOUNT_EMAIL'),
            'SOURCE_ACCOUNT_TOKEN' => env('SOURCE_ACCOUNT_TOKEN'),
            'SOURCE_CONNECTION' => env('SOURCE_CONNECTION'),
            'SOURCE_CART_VERSION' => env('SOURCE_CART_VERSION'),
            'SOURCE_VARS' => env('SOURCE_VARS'),
            'SOURCE_VALIDATED' => env('SOURCE_VALIDATED'),
            'TARGET_CART_ID' => env('TARGET_CART_ID'),
            'TARGET_STORE_URL' => env('TARGET_STORE_URL'),
            'TARGET_ACCOUNT_EMAIL' => env('TARGET_ACCOUNT_EMAIL'),
            'TARGET_ACCOUNT_TOKEN' => env('TARGET_ACCOUNT_TOKEN'),
            'TARGET_CONNECTION' => env('TARGET_CONNECTION'),
            'TARGET_CART_VERSION' => env('TARGET_CART_VERSION'),
            'TARGET_VARS' => env('TARGET_VARS'),
            'TARGET_VALIDATED' => env('TARGET_VALIDATED'),
        ]);
    }
};
