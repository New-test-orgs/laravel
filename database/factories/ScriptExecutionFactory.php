<?php

namespace Database\Factories;

use App\Models\ScriptExecution;
use App\Models\StoreMigration;
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
            'script' => 'demo-script',
            'status' => 'queued',
            'processed' => 0,
            'total' => 5000,
            'commit' => 'pending',
            'url' => 'https://shop.example/products/old-url-key',
            'requested_by' => 'You',
            'initials' => 'YO',
            'avatar' => 'teal',
            'started_at' => now(),
            'finished_at' => null,
        ];
    }
}
