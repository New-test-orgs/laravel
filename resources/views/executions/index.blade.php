<x-layouts.app :title="'Script Executions'">
    <div class="mx-auto min-h-screen max-w-[1180px] px-5 py-6 sm:px-8 sm:py-8">
        <div class="mb-8 flex items-start justify-between gap-4">
            <nav class="flex items-center gap-2 text-[13px] text-zinc-400">
                <svg class="size-4 text-brand" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" />
                </svg>
                <span>Journal</span>
                <span class="text-zinc-300">/</span>
                <a href="{{ route('migrations.index') }}" class="transition hover:text-zinc-600">Migrations</a>
                <span class="text-zinc-300">/</span>
                <span class="text-zinc-500">#{{ $migration['id'] }}</span>
            </nav>
            <span class="rounded-full bg-zinc-200/80 px-2.5 py-1 text-[10px] font-semibold tracking-[0.14em] text-zinc-500 uppercase">
                Concept preview — not live data
            </span>
        </div>

        <header class="mb-6 flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h1 class="text-[32px] leading-none font-semibold tracking-tight text-ink">Script Executions</h1>
                <div class="mt-3 flex flex-wrap items-center gap-2.5 text-sm text-zinc-500">
                    <span>{{ $migration['source'] }} → {{ $migration['target'] }}</span>
                    <span class="rounded-md bg-zinc-200/90 px-2 py-0.5 font-mono text-[12px] text-zinc-600">
                        migration #{{ $migration['id'] }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-zinc-600">
                        <span class="size-1.5 rounded-full bg-emerald-500"></span>
                        {{ $migration['status'] }}
                    </span>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                <a
                    href="{{ $migration['repository_url'] }}"
                    class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-zinc-700 shadow-xs transition hover:bg-zinc-50"
                >
                    <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0 1 12 6.844a9.59 9.59 0 0 1 2.504.337c1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.02 10.02 0 0 0 22 12.017C22 6.484 17.522 2 12 2Z" />
                    </svg>
                    {{ $migration['repository'] }}
                </a>

                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-lg bg-brand px-3.5 py-2 text-sm font-medium text-white shadow-xs transition hover:bg-brand-dark"
                    onclick="openPreviewModal('run-script-modal')"
                >
                    <svg class="size-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M8 5.14v13.72a1 1 0 0 0 1.53.85l10.06-6.86a1 1 0 0 0 0-1.7L9.53 4.29A1 1 0 0 0 8 5.14Z" />
                    </svg>
                    Run {{ $migration['run_script'] }}
                </button>
            </div>
        </header>

        <x-modal
            name="run-script-modal"
            :title="'Run '.$migration['run_script']"
            :action="route('executions.run', $migration['id'])"
            :submit="'Queue run'"
            :open="$errors->hasAny(['url', 'store_side'])"
        >
            <x-slot:description>Paste a GitHub file permalink and choose the store the script should run against.</x-slot:description>

            <fieldset>
                <legend class="mb-1.5 block text-[13px] font-medium text-zinc-600">Store</legend>
                <div class="grid grid-cols-2 gap-2.5">
                    <label class="flex cursor-pointer items-start gap-2.5 rounded-lg border border-zinc-200 bg-white px-3 py-2.5 shadow-xs has-checked:border-brand has-checked:ring-2 has-checked:ring-brand/20">
                        <input
                            type="radio"
                            name="store_side"
                            value="source"
                            class="mt-1 size-4 accent-brand"
                            @checked(old('store_side', 'source') === 'source')
                            required
                        />
                        <span>
                            <span class="block text-sm font-medium text-ink">Source</span>
                            <span class="mt-0.5 block text-[12px] text-zinc-500">{{ $migration['source'] }}</span>
                        </span>
                    </label>
                    <label class="flex cursor-pointer items-start gap-2.5 rounded-lg border border-zinc-200 bg-white px-3 py-2.5 shadow-xs has-checked:border-brand has-checked:ring-2 has-checked:ring-brand/20">
                        <input
                            type="radio"
                            name="store_side"
                            value="target"
                            class="mt-1 size-4 accent-brand"
                            @checked(old('store_side') === 'target')
                            required
                        />
                        <span>
                            <span class="block text-sm font-medium text-ink">Target</span>
                            <span class="mt-0.5 block text-[12px] text-zinc-500">{{ $migration['target'] }}</span>
                        </span>
                    </label>
                </div>
                @error('store_side')
                    <span class="mt-1.5 block text-[12px] text-red-600">{{ $message }}</span>
                @enderror
            </fieldset>

            <label class="block">
                <span class="mb-1.5 block text-[13px] font-medium text-zinc-600">GitHub URL</span>
                <input
                    type="url"
                    name="url"
                    value="{{ old('url') }}"
                    required
                    autofocus
                    placeholder="{{ $migration['script_url_placeholder'] }}"
                    class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3.5 text-sm text-ink shadow-xs outline-none transition placeholder:text-zinc-400 focus:border-brand focus:ring-2 focus:ring-brand/20"
                />
                @error('url')
                    <span class="mt-1.5 block text-[12px] text-red-600">{{ $message }}</span>
                @enderror
            </label>
        </x-modal>

        @if (session('status'))
            <p class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
                {{ session('status') }}
            </p>
        @endif

        <section class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-[0_1px_2px_rgba(16,24,40,0.04)]">
            <div class="flex items-center justify-between border-b border-zinc-100 px-5 py-4">
                <h2 class="text-sm font-medium text-ink">History for this migration</h2>
                <p class="text-sm text-zinc-400">{{ count($executions) }} executions</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[920px] text-left">
                    <thead>
                        <tr class="border-b border-zinc-100 text-[11px] font-medium tracking-[0.08em] text-zinc-400 uppercase">
                            <th class="px-5 py-3 font-medium">Script</th>
                            <th class="px-3 py-3 font-medium">Store</th>
                            <th class="px-3 py-3 font-medium">Status</th>
                            <th class="px-3 py-3 font-medium">Progress</th>
                            <th class="px-3 py-3 font-medium">Commit</th>
                            <th class="px-3 py-3 font-medium">Requested by</th>
                            <th class="px-3 py-3 font-medium">Started</th>
                            <th class="px-5 py-3 font-medium">Duration</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @foreach ($executions as $execution)
                            @php
                                $percent = (int) round(($execution['processed'] / $execution['total']) * 100);
                                $statusStyles = [
                                    'completed' => 'bg-emerald-50 text-emerald-700',
                                    'running' => 'bg-sky-50 text-sky-700',
                                    'queued' => 'bg-amber-50 text-amber-700',
                                    'failed' => 'bg-red-50 text-red-600',
                                    'cancelled' => 'bg-zinc-100 text-zinc-500',
                                ];
                                $dotStyles = [
                                    'completed' => 'bg-emerald-500',
                                    'running' => 'bg-sky-500',
                                    'queued' => 'bg-amber-500',
                                    'failed' => 'bg-red-500',
                                    'cancelled' => 'bg-zinc-400',
                                ];
                                $barStyles = [
                                    'completed' => 'bg-emerald-500',
                                    'running' => 'bg-sky-400',
                                    'queued' => 'bg-amber-400',
                                    'failed' => 'bg-zinc-300',
                                    'cancelled' => 'bg-zinc-300',
                                ];
                                $avatarStyles = [
                                    'teal' => 'bg-teal-700 text-white',
                                    'amber' => 'bg-amber-500 text-white',
                                    'violet' => 'bg-violet-500 text-white',
                                ];
                            @endphp
                            <tr class="text-sm">
                                <td class="px-5 py-4">
                                    <button
                                        type="button"
                                        class="flex items-start gap-2 text-left hover:text-brand"
                                        data-run-logs
                                        data-logs-url="{{ route('executions.logs', [$migration['id'], $execution['key']]) }}"
                                        aria-expanded="false"
                                        aria-controls="run-logs-{{ $execution['key'] }}"
                                    >
                                        <span class="mt-1 text-zinc-400 transition" data-run-logs-chevron>▸</span>
                                        <span>
                                            <span class="block font-semibold text-ink">{{ $execution['script'] }}</span>
                                            <span class="mt-0.5 block font-mono text-[12px] text-zinc-400">{{ $execution['id'] }}</span>
                                        </span>
                                    </button>
                                    @if (! empty($execution['url']))
                                        <div class="mt-1 max-w-xs truncate pl-6 text-[12px] text-zinc-500" title="{{ $execution['url'] }}">
                                            {{ $execution['url'] }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-3 py-4">
                                    <span class="block font-medium text-ink">{{ $execution['store_side'] }}</span>
                                    <span class="mt-0.5 block text-[12px] text-zinc-500">{{ $execution['store_cart'] }}</span>
                                </td>
                                <td class="px-3 py-4">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[12px] font-medium {{ $statusStyles[$execution['status']] }}">
                                        <span class="size-1.5 rounded-full {{ $dotStyles[$execution['status']] }}"></span>
                                        {{ ucfirst($execution['status']) }}
                                    </span>
                                </td>
                                <td class="px-3 py-4">
                                    <div class="h-1.5 w-40 overflow-hidden rounded-full bg-zinc-100">
                                        <div class="h-full rounded-full {{ $barStyles[$execution['status']] }}" style="width: {{ $percent }}%"></div>
                                    </div>
                                    <div class="mt-1.5 text-[12px] text-zinc-400">
                                        {{ number_format($execution['processed']) }} / {{ number_format($execution['total']) }} · {{ $percent }}%
                                    </div>
                                </td>
                                <td class="px-3 py-4">
                                    <span class="inline-flex items-center gap-1.5 rounded-md bg-zinc-100 px-2 py-1 font-mono text-[12px] text-zinc-600">
                                        <svg class="size-3.5 text-zinc-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                            <circle cx="6" cy="6" r="2.2" />
                                            <circle cx="18" cy="6" r="2.2" />
                                            <circle cx="12" cy="18" r="2.2" />
                                            <path stroke-linecap="round" d="M8 6h8M12 16V8" />
                                        </svg>
                                        {{ $execution['commit'] }}
                                    </span>
                                </td>
                                <td class="px-3 py-4">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex size-7 items-center justify-center rounded-full text-[11px] font-semibold {{ $avatarStyles[$execution['avatar']] }}">
                                            {{ $execution['initials'] }}
                                        </span>
                                        <span class="text-zinc-700">{{ $execution['requested_by'] }}</span>
                                    </div>
                                </td>
                                <td class="px-3 py-4 whitespace-nowrap text-zinc-500">{{ $execution['started'] }}</td>
                                <td class="px-5 py-4 text-zinc-500">{{ $execution['duration'] }}</td>
                            </tr>
                            <tr id="run-logs-{{ $execution['key'] }}" class="hidden" hidden data-run-logs-panel>
                                <td colspan="8" class="bg-zinc-50 px-5 py-4">
                                    <div data-run-logs-body class="text-[13px] text-zinc-400">Loading runner logs…</div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <p class="mt-5 text-[12px] text-zinc-400">
            Mock-up of a Cart2Cart “Custom Script Execution” feature. Replace this preview data with live API records when the backend is wired up.
        </p>
    </div>

    <script>
        const levelStyles = {
            debug: 'text-zinc-400',
            info: 'text-sky-300',
            notice: 'text-sky-300',
            warning: 'text-amber-300',
            error: 'text-red-400',
            critical: 'text-red-400',
            alert: 'text-red-400',
            emergency: 'text-red-400',
        };

        const addText = (parent, text, className) => {
            const span = document.createElement('span');
            span.className = className;
            span.textContent = text;
            parent.appendChild(span);
        };

        const renderLogs = (body, logs) => {
            body.replaceChildren();

            if (! Array.isArray(logs) || logs.length === 0) {
                body.className = 'text-[13px] text-zinc-400';
                body.textContent = 'No runner output yet.';
                return;
            }

            const consoleBox = document.createElement('div');
            consoleBox.className = 'overflow-x-auto rounded-xl bg-zinc-950 px-4 py-3 font-mono text-[12px] leading-6 text-zinc-300';

            logs.forEach((log) => {
                const line = document.createElement('div');
                line.className = 'flex flex-wrap gap-x-3';

                addText(line, log.logged_at ?? '', 'text-zinc-500');
                addText(line, log.level ?? '', `w-16 uppercase ${levelStyles[log.level] ?? 'text-zinc-400'}`);
                addText(line, log.message ?? '', '');

                if (log.context_text) {
                    addText(line, log.context_text, 'text-zinc-500');
                }

                consoleBox.appendChild(line);
            });

            body.className = '';
            body.appendChild(consoleBox);
        };

        document.querySelectorAll('[data-run-logs]').forEach((button) => {
            button.addEventListener('click', async () => {
                const row = button.closest('tr');
                const panel = row?.nextElementSibling;
                const body = panel?.querySelector('[data-run-logs-body]');
                const chevron = button.querySelector('[data-run-logs-chevron]');

                if (! (panel instanceof HTMLTableRowElement) || ! body) {
                    return;
                }

                const isOpen = ! panel.hasAttribute('hidden');

                if (isOpen) {
                    panel.hidden = true;
                    panel.classList.add('hidden');
                    button.setAttribute('aria-expanded', 'false');
                    chevron?.classList.remove('rotate-90');
                    return;
                }

                panel.hidden = false;
                panel.classList.remove('hidden');
                button.setAttribute('aria-expanded', 'true');
                chevron?.classList.add('rotate-90');
                body.className = 'text-[13px] text-zinc-400';
                body.textContent = 'Loading runner logs…';

                try {
                    const response = await fetch(button.dataset.logsUrl, {
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (! response.ok) {
                        throw new Error('Failed to load runner logs.');
                    }

                    const payload = await response.json();
                    renderLogs(body, payload.logs);
                } catch {
                    body.textContent = 'Could not load runner logs.';
                }
            });
        });
    </script>
</x-layouts.app>
