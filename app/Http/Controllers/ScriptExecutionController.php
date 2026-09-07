<?php

namespace App\Http\Controllers;

use App\Rules\Github\GithubScriptUrl;
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

    public function run(int $migration, Request $request, PreviewJournal $journal, GithubScriptUrl $githubScriptUrl): RedirectResponse
    {
        abort_unless($journal->find($migration) !== null, 404);

        $request->validate([
            'url' => ['required', 'string', 'url', 'max:2048', $githubScriptUrl],
        ]);

        $script = $githubScriptUrl->reference;

        abort_if($script === null, 500);

        $journal->queueRun($migration, $script);

        return back()->with('status', "Queued demo-script for migration #{$migration} with {$script->url}.");
    }
}
