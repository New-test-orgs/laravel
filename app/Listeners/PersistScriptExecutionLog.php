<?php

namespace App\Listeners;

use App\Models\ScriptExecutionLog;
use Illuminate\Log\Events\MessageLogged;
use Stringable;
use Throwable;

class PersistScriptExecutionLog
{
    private static bool $persisting = false;

    public function handle(MessageLogged $event): void
    {
        if (self::$persisting) {
            return;
        }

        $scriptExecutionId = $this->contextId($event->context, 'script_execution_id');
        $storeMigrationId = $this->contextId($event->context, 'store_migration_id');

        if ($scriptExecutionId === null || $storeMigrationId === null) {
            return;
        }

        self::$persisting = true;

        try {
            ScriptExecutionLog::query()->create([
                'script_execution_id' => $scriptExecutionId,
                'store_migration_id' => $storeMigrationId,
                'level' => $event->level,
                'message' => (string) $event->message,
                'context' => $this->serializableContext($event->context),
            ]);
        } catch (Throwable) {
            // Ignore persistence failures so a logging loop cannot take down the worker.
        } finally {
            self::$persisting = false;
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function contextId(array $context, string $key): ?int
    {
        $value = $context[$key] ?? null;

        if (is_int($value) && $value > 0) {
            return $value;
        }

        if (is_string($value) && ctype_digit($value) && (int) $value > 0) {
            return (int) $value;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function serializableContext(array $context): array
    {
        return $this->sanitize($context);
    }

    private function sanitize(mixed $value): mixed
    {
        if ($value instanceof Throwable) {
            return [
                'class' => $value::class,
                'message' => $value->getMessage(),
            ];
        }

        if (is_array($value)) {
            return array_map(fn (mixed $item): mixed => $this->sanitize($item), $value);
        }

        if ($value instanceof Stringable) {
            return (string) $value;
        }

        if (is_object($value)) {
            return $value::class;
        }

        if (is_resource($value)) {
            return 'resource';
        }

        return $value;
    }
}
