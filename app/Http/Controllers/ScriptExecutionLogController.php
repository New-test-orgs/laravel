<?php

namespace App\Http\Controllers;

use App\Support\PreviewJournal;
use Illuminate\Http\JsonResponse;

class ScriptExecutionLogController extends Controller
{
    public function index(int $migration, int $execution, PreviewJournal $journal): JsonResponse
    {
        abort_unless($journal->find($migration) !== null, 404);

        $logs = $journal->logsFor($migration, $execution);

        abort_unless($logs !== null, 404);

        return response()->json([
            'logs' => $logs,
        ]);
    }
}
