<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BoostRun extends Model
{
    protected $fillable = [
        'boost_request_id',
        'post_master_id',
        'run_id',
        'requested_by',
        'status',
        'budget_total_cents',
        'currency',
        'duration_days',
        'targeting_json',
    ];

    protected $casts = [
        'targeting_json'     => 'array',
        'budget_total_cents' => 'integer',
        'duration_days'      => 'integer',
    ];

    public function boostRequest(): BelongsTo
    {
        return $this->belongsTo(BoostRequest::class, 'boost_request_id');
    }

    public function postMaster(): BelongsTo
    {
        return $this->belongsTo(FacebookPost::class, 'post_master_id');
    }

    public function syncRun(): BelongsTo
    {
        return $this->belongsTo(SyncRun::class, 'run_id');
    }

    public function adsEntity(): HasOne
    {
        return $this->hasOne(AdsEntity::class, 'boost_run_id');
    }

    // ─────────────────────────────────────────────────────────
    // Budget en unité lisible (inverse des centimes)
    // ─────────────────────────────────────────────────────────

    public function getBudgetReadableAttribute(): float
    {
        if ($this->currency === 'XOF') {
            return $this->budget_total_cents;
        }
        return $this->budget_total_cents / 100;
    }
}
