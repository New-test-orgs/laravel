<?php

namespace App\Http\Controllers;

use App\Rules\Github\GithubScriptUrl;
use App\StoreSide;
use App\Support\PreviewJournal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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

        $validated = $request->validate([
            'url' => ['required', 'string', 'url', 'max:2048', $githubScriptUrl],
            'store_side' => ['required', Rule::enum(StoreSide::class)],
        ]);

        $script = $githubScriptUrl->reference;

        abort_if($script === null, 500);

        $storeSide = StoreSide::from($validated['store_side']);

        $journal->queueRun($migration, $script, $storeSide);

        return back()->with(
            'status',
            "Queued {$script->filename()} for the {$storeSide->label()} store on migration #{$migration}.",
        );
    }
}
