<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacebookPost extends Model
{
    protected $fillable = [
        'post_id',
        'facebook_page_id',
        'message',
        'thumbnail_url',
        'permalink_url',
        'type',
        'impressions',
        'posted_at',
        'last_synced_at',
    ];

    protected $casts = [
        'impressions'    => 'integer',
        'posted_at'      => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(FacebookPage::class, 'facebook_page_id');
    }
}
