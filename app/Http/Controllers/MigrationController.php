<?php

namespace App\Http\Controllers;

use App\Cart2Cart\Cart2CartClient;
use App\Cart2Cart\Cart2CartException;
use App\Support\PreviewJournal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MigrationController extends Controller
{
    public function index(PreviewJournal $journal): View
    {
        return view('migrations.index', [
            'migrations' => $journal->migrations(),
        ]);
    }

    public function store(Request $request, PreviewJournal $journal, Cart2CartClient $cart2Cart): RedirectResponse
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
        ]);

        $id = (int) $validated['id'];

        try {
            $access = $cart2Cart->storeAccess($id);
        } catch (Cart2CartException $exception) {
            throw ValidationException::withMessages([
                'id' => $exception->getMessage(),
            ]);
        }

        $migration = $journal->create(
            $access->source->cartLabel(),
            $access->target->cartLabel(),
            $id,
        );

        return back()->with(
            'status',
            "Started temporary migration #{$migration['id']}.",
        );
    }
}
