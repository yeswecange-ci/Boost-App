<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SyncRun extends Model
{
    // Pas de created_at/updated_at Laravel — on utilise started_at/finished_at
    public $timestamps = false;

    protected $fillable = [
        'source',
        'page_id',
        'status',
        'started_at',
        'finished_at',
        'note',
    ];

    protected $casts = [
        'started_at'  => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function errors(): HasMany
    {
        return $this->hasMany(SyncError::class, 'run_id');
    }

    public function postHistories(): HasMany
    {
        return $this->hasMany(PostHistory::class, 'run_id');
    }

    public function mediaHistories(): HasMany
    {
        return $this->hasMany(PostMediaHistory::class, 'run_id');
    }

    public function boostRuns(): HasMany
    {
        return $this->hasMany(BoostRun::class, 'run_id');
    }

    // ─────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────

    public function finish(?string $note = null): void
    {
        $this->update([
            'status'      => 'FINISHED',
            'finished_at' => now(),
            'note'        => $note,
        ]);
    }

    public function fail(?string $note = null): void
    {
        $this->update([
            'status'      => 'FAILED',
            'finished_at' => now(),
            'note'        => $note ? substr($note, 0, 255) : null,
        ]);
    }

    public function isRunning(): bool
    {
        return $this->status === 'RUNNING';
    }

    public function getDurationAttribute(): ?string
    {
        if (!$this->finished_at) {
            return null;
        }
        $seconds = $this->started_at->diffInSeconds($this->finished_at);
        return $seconds < 60 ? "{$seconds}s" : round($seconds / 60, 1) . 'min';
    }
}
