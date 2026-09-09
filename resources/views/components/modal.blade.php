@props([
    'name',
    'title',
    'action',
    'submit',
    'loading' => 'Loading…',
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
    <form method="POST" action="{{ $action }}" class="relative flex flex-col" onsubmit="return setPreviewModalLoading(this) !== false">
        @csrf

        <div
            data-preview-modal-loading
            class="absolute inset-0 z-10 hidden items-center justify-center gap-3 rounded-2xl bg-white/85"
            aria-hidden="true"
        >
            <svg class="size-8 animate-spin text-brand" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" />
                <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V1C5.373 1 1 5.373 1 12h3z" />
            </svg>
            <p class="text-sm font-medium text-zinc-600">{{ $loading }}</p>
        </div>

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
            @if ($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800" role="alert">
                    @foreach ($errors->all() as $message)
                        <p @class(['mt-1' => ! $loop->first])>{{ $message }}</p>
                    @endforeach
                </div>
            @endif

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
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand px-3.5 py-2 text-sm font-medium text-white shadow-xs transition hover:bg-brand-dark"
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

            if (! modal || modal.dataset.loading === 'true' || typeof modal.close !== 'function' || ! modal.open) {
                return;
            }

            modal.close();
        };

        window.setPreviewModalLoading = function (form) {
            if (! (form instanceof HTMLFormElement)) {
                return;
            }

            if (form.dataset.submitting === 'true') {
                return false;
            }

            form.dataset.submitting = 'true';

            const modal = form.closest('dialog');

            if (modal) {
                modal.dataset.loading = 'true';
                modal.setAttribute('aria-busy', 'true');
            }

            const overlay = form.querySelector('[data-preview-modal-loading]');

            if (overlay) {
                overlay.classList.remove('hidden');
                overlay.classList.add('flex', 'flex-col');
                overlay.setAttribute('aria-hidden', 'false');
            }

            form.querySelectorAll('button').forEach(function (element) {
                if (element.type === 'submit') {
                    return;
                }

                element.disabled = true;
            });
        };

        document.addEventListener('cancel', function (event) {
            const modal = event.target;

            if (modal instanceof HTMLDialogElement && modal.dataset.loading === 'true') {
                event.preventDefault();
            }
        });

        document.addEventListener('click', function (event) {
            const modal = event.target;

            if (! (modal instanceof HTMLDialogElement) || ! modal.hasAttribute('data-preview-modal')) {
                return;
            }

            if (modal.dataset.loading === 'true') {
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
