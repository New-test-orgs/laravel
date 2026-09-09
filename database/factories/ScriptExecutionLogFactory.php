<?php

namespace Database\Factories;

use App\Models\ScriptExecution;
use App\Models\ScriptExecutionLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScriptExecutionLog>
 */
class ScriptExecutionLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'script_execution_id' => ScriptExecution::factory(),
            'store_migration_id' => function (array $attributes): int {
                return ScriptExecution::query()->findOrFail($attributes['script_execution_id'])->store_migration_id;
            },
            'level' => 'info',
            'message' => 'Migration script step completed',
            'context' => [
                'step' => 1,
            ],
        ];
    }
}
