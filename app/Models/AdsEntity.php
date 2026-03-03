<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdsEntity extends Model
{
    protected $fillable = [
        'boost_run_id',
        'campaign_id',
        'adset_id',
        'ad_id',
        'campaign_status',
        'adset_status',
        'ad_status',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function boostRun(): BelongsTo
    {
        return $this->belongsTo(BoostRun::class, 'boost_run_id');
    }

    public function isComplete(): bool
    {
        return $this->campaign_id && $this->adset_id && $this->ad_id;
    }
}
