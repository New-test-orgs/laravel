<x-layouts.app :title="'Migrations'">
    <div class="mx-auto min-h-screen max-w-[1180px] px-5 py-6 sm:px-8 sm:py-8">
        <div class="mb-8 flex items-start justify-between gap-4">
            <nav class="flex items-center gap-2 text-[13px] text-zinc-400">
                <svg class="size-4 text-brand" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" />
                </svg>
                <span>Journal</span>
                <span class="text-zinc-300">/</span>
                <span class="text-zinc-500">Migrations</span>
            </nav>
            <span class="rounded-full bg-zinc-200/80 px-2.5 py-1 text-[10px] font-semibold tracking-[0.14em] text-zinc-500 uppercase">
                Concept preview — not live data
            </span>
        </div>

        <header class="mb-6 flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h1 class="text-[32px] leading-none font-semibold tracking-tight text-ink">Migrations</h1>
                <p class="mt-3 text-sm text-zinc-500">Store-to-store jobs that scripts can run against.</p>
            </div>

            <button
                type="button"
                class="inline-flex items-center gap-2 rounded-lg bg-brand px-3.5 py-2 text-sm font-medium text-white shadow-xs transition hover:bg-brand-dark"
                onclick="openPreviewModal('new-migration-modal')"
            >
                New migration
            </button>
        </header>

        @if (session('status'))
            <p class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
                {{ session('status') }}
            </p>
        @endif

        <x-modal
            name="new-migration-modal"
            title="Start a migration"
            :action="route('migrations.store')"
            submit="Start migration"
            :open="$errors->hasAny(['id', 'url', 'source', 'target'])"
        >
            <x-slot:description>Identify the Cart2Cart job and the GitHub commit permalink the first script should run from.</x-slot:description>

            <label class="block">
                <span class="mb-1.5 block text-[13px] font-medium text-zinc-600">Migration ID</span>
                <input
                    type="number"
                    name="id"
                    min="1"
                    required
                    value="{{ old('id') }}"
                    placeholder="92831"
                    class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3.5 font-mono text-sm text-ink shadow-xs outline-none transition placeholder:text-zinc-400 focus:border-brand focus:ring-2 focus:ring-brand/20"
                />
                @error('id')
                    <span class="mt-1.5 block text-[12px] text-red-600">{{ $message }}</span>
                @enderror
            </label>

            <label class="block">
                <span class="mb-1.5 block text-[13px] font-medium text-zinc-600">GitHub URL</span>
                <input
                    type="url"
                    name="url"
                    required
                    value="{{ old('url') }}"
                    placeholder="{{ $scriptUrlPlaceholder }}"
                    class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3.5 text-sm text-ink shadow-xs outline-none transition placeholder:text-zinc-400 focus:border-brand focus:ring-2 focus:ring-brand/20"
                />
                @error('url')
                    <span class="mt-1.5 block text-[12px] text-red-600">{{ $message }}</span>
                @enderror
            </label>

            <label class="block">
                <span class="mb-1.5 block text-[13px] font-medium text-zinc-600">Source</span>
                <select
                    name="source"
                    class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3.5 text-sm text-ink shadow-xs outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/20"
                >
                    <option value="">Optional</option>
                    @foreach ($platforms as $platform)
                        <option value="{{ $platform }}" @selected(old('source') === $platform)>{{ $platform }}</option>
                    @endforeach
                </select>
                @error('source')
                    <span class="mt-1.5 block text-[12px] text-red-600">{{ $message }}</span>
                @enderror
            </label>

            <label class="block">
                <span class="mb-1.5 block text-[13px] font-medium text-zinc-600">Target</span>
                <select
                    name="target"
                    class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3.5 text-sm text-ink shadow-xs outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/20"
                >
                    <option value="">Optional</option>
                    @foreach ($platforms as $platform)
                        <option value="{{ $platform }}" @selected(old('target') === $platform)>{{ $platform }}</option>
                    @endforeach
                </select>
                @error('target')
                    <span class="mt-1.5 block text-[12px] text-red-600">{{ $message }}</span>
                @enderror
            </label>
        </x-modal>

        <section class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-[0_1px_2px_rgba(16,24,40,0.04)]">
            <div class="flex items-center justify-between border-b border-zinc-100 px-5 py-4">
                <h2 class="text-sm font-medium text-ink">All migrations</h2>
                <p class="text-sm text-zinc-400">{{ count($migrations) }} migrations</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[920px] text-left">
                    <thead>
                        <tr class="border-b border-zinc-100 text-[11px] font-medium tracking-[0.08em] text-zinc-400 uppercase">
                            <th class="px-5 py-3 font-medium">Migration</th>
                            <th class="px-3 py-3 font-medium">Status</th>
                            <th class="px-3 py-3 font-medium">Last script</th>
                            <th class="px-3 py-3 font-medium">Executions</th>
                            <th class="px-3 py-3 font-medium">Owner</th>
                            <th class="px-5 py-3 font-medium">Started</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @foreach ($migrations as $migration)
                            @php
                                $statusStyles = [
                                    'active' => 'bg-emerald-50 text-emerald-700',
                                    'completed' => 'bg-emerald-50 text-emerald-700',
                                    'failed' => 'bg-red-50 text-red-600',
                                    'cancelled' => 'bg-zinc-100 text-zinc-500',
                                ];
                                $dotStyles = [
                                    'active' => 'bg-emerald-500',
                                    'completed' => 'bg-emerald-500',
                                    'failed' => 'bg-red-500',
                                    'cancelled' => 'bg-zinc-400',
                                ];
                                $avatarStyles = [
                                    'teal' => 'bg-teal-700 text-white',
                                    'amber' => 'bg-amber-500 text-white',
                                    'violet' => 'bg-violet-500 text-white',
                                ];
                            @endphp
                            <tr class="text-sm">
                                <td class="px-5 py-4">
                                    <a href="{{ route('executions.index', $migration['id']) }}" class="font-semibold text-ink hover:text-brand">
                                        {{ $migration['source'] }} → {{ $migration['target'] }}
                                    </a>
                                    <div class="mt-0.5 font-mono text-[12px] text-zinc-400">#{{ $migration['id'] }}</div>
                                </td>
                                <td class="px-3 py-4">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[12px] font-medium {{ $statusStyles[$migration['status']] }}">
                                        <span class="size-1.5 rounded-full {{ $dotStyles[$migration['status']] }}"></span>
                                        {{ ucfirst($migration['status']) }}
                                    </span>
                                </td>
                                <td class="px-3 py-4">
                                    <div class="font-medium text-ink">{{ $migration['last_script'] }}</div>
                                    <div class="mt-0.5 font-mono text-[12px] text-zinc-400">{{ $migration['repository'] }}</div>
                                </td>
                                <td class="px-3 py-4 text-zinc-500">{{ $migration['executions'] }}</td>
                                <td class="px-3 py-4">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex size-7 items-center justify-center rounded-full text-[11px] font-semibold {{ $avatarStyles[$migration['avatar']] }}">
                                            {{ $migration['initials'] }}
                                        </span>
                                        <span class="text-zinc-700">{{ $migration['owner'] }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-zinc-500">{{ $migration['started'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <p class="mt-5 text-[12px] text-zinc-400">
            Mock-up of a Cart2Cart migrations journal. Open a migration to review its custom script executions.
        </p>
    </div>
</x-layouts.app>
