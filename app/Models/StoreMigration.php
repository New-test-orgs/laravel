<?php

namespace App\Models;

use Database\Factories\StoreMigrationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'id',
    'source',
    'target',
    'status',
    'repository',
    'owner',
    'initials',
    'avatar',
    'started_at',
])]
class StoreMigration extends Model
{
    /** @use HasFactory<StoreMigrationFactory> */
    use HasFactory;

    public $incrementing = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'started_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<ScriptExecution, $this>
     */
    public function scriptExecutions(): HasMany
    {
        return $this->hasMany(ScriptExecution::class);
    }

    /**
     * @return HasOne<ScriptExecution, $this>
     */
    public function latestScriptExecution(): HasOne
    {
        return $this->hasOne(ScriptExecution::class)->latestOfMany('started_at');
    }

    /**
     * @return array<string, mixed>
     */
    public function toJournalRow(): array
    {
        return [
            'id' => $this->id,
            'source' => $this->source,
            'target' => $this->target,
            'status' => $this->status,
            'repository' => $this->repository,
            'executions' => $this->script_executions_count ?? $this->scriptExecutions()->count(),
            'last_script' => $this->latestScriptExecution?->script ?: '—',
            'started' => $this->started_at->format('M j, H:i'),
            'owner' => $this->owner,
            'initials' => $this->initials,
            'avatar' => $this->avatar,
        ];
    }
}
