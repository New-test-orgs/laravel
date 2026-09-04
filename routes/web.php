<?php

use App\Http\Controllers\ScriptExecutionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ScriptExecutionController::class, 'index'])->name('executions.index');
Route::post('/executions/run', [ScriptExecutionController::class, 'run'])->name('executions.run');
