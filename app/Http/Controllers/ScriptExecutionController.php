<?php

namespace App\Http\Controllers;

use App\Support\PreviewJournal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScriptExecutionController extends Controller
{
    public function index(int $migration, PreviewJournal $journal): View
    {
        $record = $journal->find($migration);

        abort_unless($record !== null, 404);

        return view('executions.index', [
            'migration' => $record,
            'executions' => $journal->executionsFor($migration),
        ]);
    }

    public function run(int $migration, Request $request, PreviewJournal $journal): RedirectResponse
    {
        abort_unless($journal->find($migration) !== null, 404);

        $validated = $request->validate([
            'url' => ['required', 'string', 'url', 'max:2048'],
        ]);

        $journal->queueRun($migration, $validated['url']);

        return back()->with('status', "Queued demo-script for migration #{$migration} with {$validated['url']}.");
    }
}
