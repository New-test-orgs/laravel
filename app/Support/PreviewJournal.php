<?php

namespace App\Support;

use App\Github\GithubScriptReference;
use App\Jobs\RunScriptExecution;
use App\Models\ScriptExecution;
use App\Models\StoreMigration;
use App\Scripts\DemoMigrationScript;

class PreviewJournal
{
    /**
     * @var list<string>
     */
    public const PLATFORMS = [
        'Magento 1.9',
        'Magento 2',
        'Shopify',
        'Shopify Plus',
        'WooCommerce',
        'BigCommerce',
        'OpenCart',
        'PrestaShop',
    ];

    /**
     * @return list<string>
     */
    public function platforms(): array
    {
        return self::PLATFORMS;
    }

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
    public function create(?string $source, ?string $target, int $id): array
    {
        return StoreMigration::query()->create([
            'id' => $id,
            'source' => $source ?: 'Unspecified',
            'target' => $target ?: 'Unspecified',
            'status' => 'active',
            'repository' => 'cart2cart-migration-scripts',
            'owner' => 'You',
            'initials' => 'YO',
            'avatar' => 'teal',
            'started_at' => now(),
        ])->toJournalRow();
    }

    /**
     * @return array<string, mixed>
     */
    public function queueRun(int $migrationId, GithubScriptReference $script): array
    {
        $migration = StoreMigration::query()->findOrFail($migrationId);

        $execution = $migration->scriptExecutions()->create([
            'script' => DemoMigrationScript::NAME,
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

        return $execution->toJournalRow();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function executionsFor(int $migrationId): array
    {
        return ScriptExecution::query()
            ->where('store_migration_id', $migrationId)
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (ScriptExecution $execution): array => $execution->toJournalRow())
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
            'repository_url' => 'https://github.com/'.config('services.github.allowed_repositories')[0],
            'run_script' => DemoMigrationScript::NAME,
        ];
    }
}
