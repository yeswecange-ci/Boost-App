<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignAnalytics extends Model
{
    protected $table = 'campaign_analytics';

    protected $fillable = [
        'boost_campaign_id',
        'date_snapshot',
        'impressions',
        'reach',
        'clicks',
        'spend',
        'cpm',
        'cpc',
        'ctr',
    ];

    protected $casts = [
        'date_snapshot' => 'date',
        'impressions'   => 'integer',
        'reach'         => 'integer',
        'clicks'        => 'integer',
        'spend'         => 'float',
        'cpm'           => 'float',
        'cpc'           => 'float',
        'ctr'           => 'float',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(BoostCampaign::class, 'boost_campaign_id');
    }
}
