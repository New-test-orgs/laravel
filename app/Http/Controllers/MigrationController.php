<?php

namespace App\Http\Controllers;

use App\Support\PreviewJournal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MigrationController extends Controller
{
    public function index(PreviewJournal $journal): View
    {
        return view('migrations.index', [
            'migrations' => $journal->migrations(),
            'platforms' => $journal->platforms(),
        ]);
    }

    public function store(Request $request, PreviewJournal $journal): RedirectResponse
    {
        $validated = $request->validate([
            'id' => [
                'required',
                'integer',
                'min:1',
                function (string $attribute, mixed $value, \Closure $fail) use ($journal): void {
                    if ($journal->find((int) $value) !== null) {
                        $fail('This migration ID is already in the journal.');
                    }
                },
            ],
            'url' => ['required', 'string', 'url', 'max:2048'],
            'source' => ['nullable', 'string', Rule::in($journal->platforms())],
            'target' => ['nullable', 'string', Rule::in($journal->platforms())],
        ]);

        $migration = $journal->create(
            $validated['source'] ?? null,
            $validated['target'] ?? null,
            (int) $validated['id'],
        );

        $journal->queueRun($migration['id'], $validated['url']);

        return back()->with(
            'status',
            "Started temporary migration #{$migration['id']} and queued a run for {$validated['url']}.",
        );
    }
}
