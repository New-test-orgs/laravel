<?php

namespace Tests\Feature;

use App\Models\ScriptExecution;
use App\Models\ScriptExecutionLog;
use App\Models\StoreMigration;
use Tests\TestCase;

class ScriptExecutionLogControllerTest extends TestCase
{
    public function test_returns_logs_for_the_execution_and_migration(): void
    {
        $this->travelTo('2026-09-07 17:01:02');

        $migration = StoreMigration::factory()->create([
            'id' => 92831,
        ]);
        $execution = ScriptExecution::factory()->for($migration)->create();
        $log = ScriptExecutionLog::factory()->for($execution)->create([
            'message' => 'Migration script step completed',
            'level' => 'info',
            'context' => ['step' => 2],
        ]);

        $this->getJson(route('executions.logs', [$migration, $execution]))
            ->assertOk()
            ->assertExactJson([
                'logs' => [
                    [
                        'id' => $log->id,
                        'level' => 'info',
                        'message' => 'Migration script step completed',
                        'context_text' => 'step=2',
                        'logged_at' => '17:01:02',
                    ],
                ],
            ]);
    }

    public function test_returns_an_empty_list_when_the_run_has_no_logs(): void
    {
        $migration = StoreMigration::factory()->create([
            'id' => 92831,
        ]);
        $execution = ScriptExecution::factory()->for($migration)->create();

        $this->getJson(route('executions.logs', [$migration, $execution]))
            ->assertOk()
            ->assertExactJson([
                'logs' => [],
            ]);
    }

    public function test_returns_404_when_the_execution_belongs_to_another_migration(): void
    {
        $shown = StoreMigration::factory()->create([
            'id' => 92831,
        ]);
        $hidden = StoreMigration::factory()->create([
            'id' => 44102,
        ]);
        $execution = ScriptExecution::factory()->for($hidden)->create();
        ScriptExecutionLog::factory()->for($execution)->create([
            'message' => 'secret-other-migration-log',
        ]);

        $this->getJson(route('executions.logs', [$shown->id, $execution->id]))
            ->assertNotFound();
    }

    public function test_returns_404_for_an_unknown_migration(): void
    {
        $this->getJson('/migrations/92831/executions/1/logs')
            ->assertNotFound();
    }
}
