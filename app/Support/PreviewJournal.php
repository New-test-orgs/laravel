<?php

namespace App\Support;

use App\Github\GithubAllowedRepositories;
use App\Github\GithubScriptReference;
use App\Jobs\RunScriptExecution;
use App\Models\ScriptExecution;
use App\Models\ScriptExecutionLog;
use App\Models\StoreMigration;
use App\StoreSide;

class PreviewJournal
{
    public function __construct(private GithubAllowedRepositories $githubRepositories) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function migrations(): array
    {
        return StoreMigration::query()
            ->withCount('scriptExecutions')
            ->with('latestScriptExecution')
            ->latest('started_at')
            ->get()
            ->map(fn (StoreMigration $migration): array => $migration->toJournalRow())
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        $stored = StoreMigration::query()->find($id);

        if ($stored === null) {
            return null;
        }

        return $this->detailFor($stored->toJournalRow());
    }

    /**
     * @return array<string, mixed>
     */
    public function create(string $source, string $target, int $id): array
    {
        return StoreMigration::query()->create([
            'id' => $id,
            'source' => $source,
            'target' => $target,
            'status' => 'active',
            'repository' => GithubAllowedRepositories::REPO,
            'owner' => 'You',
            'initials' => 'YO',
            'avatar' => 'teal',
            'started_at' => now(),
        ])->toJournalRow();
    }

    /**
     * @return array<string, mixed>
     */
    public function queueRun(int $migrationId, GithubScriptReference $script, StoreSide $storeSide): array
    {
        $migration = StoreMigration::query()->findOrFail($migrationId);

        $execution = $migration->scriptExecutions()->create([
            'script' => $script->filename(),
            'store_side' => $storeSide,
            'status' => 'queued',
            'processed' => 0,
            'total' => 5000,
            'commit' => $script->sha,
            'url' => $script->url,
            'requested_by' => $migration->owner,
            'initials' => $migration->initials,
            'avatar' => $migration->avatar,
            'started_at' => now(),
            'finished_at' => null,
        ]);

        $migration->update([
            'status' => 'active',
        ]);

        RunScriptExecution::dispatch($execution);

        $execution->setRelation('storeMigration', $migration);

        return $execution->toJournalRow();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function executionsFor(int $migrationId): array
    {
        return ScriptExecution::query()
            ->with('storeMigration')
            ->where('store_migration_id', $migrationId)
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (ScriptExecution $execution): array => $execution->toJournalRow())
            ->all();
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    public function logsFor(int $migrationId, int $executionId): ?array
    {
        $execution = ScriptExecution::query()
            ->where('store_migration_id', $migrationId)
            ->whereKey($executionId)
            ->first();

        if ($execution === null) {
            return null;
        }

        return $execution->logs()
            ->orderBy('id')
            ->get()
            ->map(fn (ScriptExecutionLog $log): array => $log->toJournalRow())
            ->all();
    }

    /**
     * @param  array<string, mixed>  $migration
     * @return array<string, mixed>
     */
    private function detailFor(array $migration): array
    {
        return [
            'id' => $migration['id'],
            'source' => $migration['source'],
            'target' => $migration['target'],
            'status' => ucfirst($migration['status']),
            'repository' => $migration['repository'],
            'repository_url' => $this->githubRepositories->primaryUrl(),
            'script_url_placeholder' => $this->githubRepositories->examplePermalink(),
            'run_script' => 'script',
        ];
    }
}
