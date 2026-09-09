<?php

namespace App\Models;

use App\Cart2Cart\Cart2CartStoreAccess;
use App\Cart2Cart\Cart2CartStoreCredentials;
use App\StoreSide;
use Database\Factories\ScriptExecutionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

#[Fillable([
    'store_migration_id',
    'script',
    'store_side',
    'status',
    'processed',
    'total',
    'commit',
    'url',
    'requested_by',
    'initials',
    'avatar',
    'started_at',
    'finished_at',
])]
class ScriptExecution extends Model
{
    /** @use HasFactory<ScriptExecutionFactory> */
    use HasFactory;

    private ?Cart2CartStoreAccess $loadedStoreAccess = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'store_side' => StoreSide::class,
            'processed' => 'integer',
            'total' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<StoreMigration, $this>
     */
    public function storeMigration(): BelongsTo
    {
        return $this->belongsTo(StoreMigration::class);
    }

    /**
     * @return HasMany<ScriptExecutionLog, $this>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(ScriptExecutionLog::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function toJournalRow(): array
    {
        return [
            'script' => $this->script,
            'store_side' => $this->store_side->label(),
            'store_cart' => $this->storeCartName(),
            'key' => $this->id,
            'id' => 'execution-'.$this->id,
            'status' => $this->status,
            'processed' => $this->processed,
            'total' => $this->total,
            'commit' => $this->commit ?: 'pending',
            'requested_by' => $this->requested_by,
            'initials' => $this->initials,
            'avatar' => $this->avatar,
            'started' => $this->started_at->format('M j, H:i'),
            'duration' => $this->formattedDuration(),
            'url' => $this->url,
        ];
    }

    public function storeCartName(): string
    {
        return match ($this->store_side) {
            StoreSide::Source => $this->storeMigration->source,
            StoreSide::Target => $this->storeMigration->target,
        };
    }

    public function setStoreAccess(Cart2CartStoreAccess $storeAccess): static
    {
        $this->loadedStoreAccess = $storeAccess;

        return $this;
    }

    public function storeAccess(): Cart2CartStoreAccess
    {
        if ($this->loadedStoreAccess === null) {
            throw new LogicException('Store credentials were not loaded for this script execution.');
        }

        return $this->loadedStoreAccess;
    }

    public function storeCredentials(): Cart2CartStoreCredentials
    {
        return $this->storeAccess()->for($this->store_side);
    }

    private function formattedDuration(): string
    {
        if ($this->finished_at === null) {
            return '—';
        }

        $seconds = (int) $this->started_at->diffInSeconds($this->finished_at);
        $minutes = intdiv($seconds, 60);
        $remainder = $seconds % 60;

        if ($minutes === 0) {
            return $remainder.'s';
        }

        return $minutes.'m '.str_pad((string) $remainder, 2, '0', STR_PAD_LEFT).'s';
    }
}
