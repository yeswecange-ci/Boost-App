<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoostAnalytics extends Model
{
    protected $fillable = [
        'boost_request_id', 'date_snapshot', 'impressions', 'reach',
        'clicks', 'spend', 'cpm', 'cpc', 'ctr', 'fetched_from'
    ];

    protected $casts = ['date_snapshot' => 'date'];

    public function boostRequest()
    {
        return $this->belongsTo(BoostRequest::class);
    }
}