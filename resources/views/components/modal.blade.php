@props([
    'name',
    'title',
    'action',
    'submit',
    'open' => false,
])

<dialog
    {{ $attributes->merge([
        'id' => $name,
        'style' => 'margin-top: 20vh',
        'class' => 'w-[calc(100%-2rem)]  max-w-md mx-auto rounded-2xl border border-zinc-200/80 bg-white p-0 text-ink shadow-[0_24px_60px_rgba(24,24,27,0.18)] backdrop:bg-ink/45 backdrop:backdrop-blur-[2px]',
    ]) }}
    data-preview-modal
    aria-labelledby="{{ $name }}-title"
>
    <form method="POST" action="{{ $action }}" class="flex flex-col">
        @csrf

        <div class="flex items-start gap-3 border-b border-zinc-100 bg-canvas px-5 py-4">
            <span class="mt-0.5 inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-brand/10 text-brand">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                </svg>
            </span>

            <div class="min-w-0 flex-1">
                <h2 id="{{ $name }}-title" class="text-base font-semibold tracking-tight text-ink">{{ $title }}</h2>
                @isset($description)
                    <p class="mt-1 text-[13px] text-zinc-500">{{ $description }}</p>
                @endisset
            </div>

            <button
                type="button"
                class="inline-flex size-8 shrink-0 items-center justify-center rounded-lg text-zinc-400 transition hover:bg-white hover:text-ink"
                onclick='closePreviewModal(@json($name))'
                aria-label="Close"
            >
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                </svg>
            </button>
        </div>

        <div class="flex flex-col gap-4 px-5 py-5">
            {{ $slot }}
        </div>

        <div class="flex justify-end gap-2.5 border-t border-zinc-100 bg-canvas px-5 py-3.5">
            <button
                type="button"
                class="rounded-lg border border-zinc-200 bg-white px-3.5 py-2 text-sm font-medium text-zinc-700 shadow-xs transition hover:bg-zinc-50"
                onclick='closePreviewModal(@json($name))'
            >
                Cancel
            </button>
            <button
                type="submit"
                class="rounded-lg bg-brand px-3.5 py-2 text-sm font-medium text-white shadow-xs transition hover:bg-brand-dark"
            >
                {{ $submit }}
            </button>
        </div>
    </form>
</dialog>

@if ($open)
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            openPreviewModal(@json($name));
        });
    </script>
@endif

@once
    <script>
        window.openPreviewModal = function (id) {
            const modal = document.getElementById(id);

            if (! modal || typeof modal.showModal !== 'function' || modal.open) {
                return;
            }

            modal.showModal();
        };

        window.closePreviewModal = function (id) {
            const modal = document.getElementById(id);

            if (! modal || typeof modal.close !== 'function' || ! modal.open) {
                return;
            }

            modal.close();
        };

        document.addEventListener('click', function (event) {
            const modal = event.target;

            if (! (modal instanceof HTMLDialogElement) || ! modal.hasAttribute('data-preview-modal')) {
                return;
            }

            const panel = modal.getBoundingClientRect();
            const clickedBackdrop = event.clientX < panel.left
                || event.clientX > panel.right
                || event.clientY < panel.top
                || event.clientY > panel.bottom;

            if (clickedBackdrop) {
                modal.close();
            }
        });
    </script>
@endonce
