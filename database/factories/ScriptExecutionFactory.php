<?php

namespace Database\Factories;

use App\Github\GithubAllowedRepositories;
use App\Models\ScriptExecution;
use App\Models\StoreMigration;
use App\StoreSide;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScriptExecution>
 */
class ScriptExecutionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_migration_id' => StoreMigration::factory(),
            'script' => 'demo.php',
            'store_side' => StoreSide::Source,
            'status' => 'queued',
            'processed' => 0,
            'total' => 5000,
            'commit' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
            'url' => GithubAllowedRepositories::url().'/blob/aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa/scripts/demo.php',
            'requested_by' => 'You',
            'initials' => 'YO',
            'avatar' => 'teal',
            'started_at' => now(),
            'finished_at' => null,
        ];
    }
}
