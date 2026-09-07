<?php

use App\Http\Controllers\MigrationController;
use App\Http\Controllers\ScriptExecutionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MigrationController::class, 'index'])->name('migrations.index');
Route::post('/migrations', [MigrationController::class, 'store'])->name('migrations.store');
Route::get('/migrations/{migration}', [ScriptExecutionController::class, 'index'])->name('executions.index');
Route::post('/migrations/{migration}/executions/run', [ScriptExecutionController::class, 'run'])->name('executions.run');
