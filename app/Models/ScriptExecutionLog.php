<?php

namespace App\Models;

use Database\Factories\ScriptExecutionLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'script_execution_id',
    'store_migration_id',
    'level',
    'message',
    'context',
])]
class ScriptExecutionLog extends Model
{
    /** @use HasFactory<ScriptExecutionLogFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'context' => 'array',
        ];
    }

    /**
     * @return BelongsTo<ScriptExecution, $this>
     */
    public function scriptExecution(): BelongsTo
    {
        return $this->belongsTo(ScriptExecution::class);
    }

    /**
     * @return BelongsTo<StoreMigration, $this>
     */
    public function storeMigration(): BelongsTo
    {
        return $this->belongsTo(StoreMigration::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function toJournalRow(): array
    {
        $context = collect($this->context ?? [])
            ->except(['script_execution_id', 'store_migration_id'])
            ->all();

        return [
            'id' => $this->id,
            'level' => $this->level,
            'message' => $this->message,
            'context_text' => $this->contextText($context),
            'logged_at' => $this->created_at->format('H:i:s'),
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function contextText(array $context): string
    {
        return collect($context)
            ->map(function (mixed $value, int|string $key): string {
                $rendered = is_scalar($value) || $value === null
                    ? (string) $value
                    : json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

                return $key.'='.$rendered;
            })
            ->implode(' ');
    }
}
