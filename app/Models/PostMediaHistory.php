<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostMediaHistory extends Model
{
    protected $table = 'post_media_history';

    protected $fillable = [
        'post_master_id',
        'position',
        'media_type',
        'source_url',
        'preview_url',
        'link_url',
        'title',
        'payload',
        'row_hash',
        'run_id',
        'is_active',
        'valid_from',
        'valid_to',
    ];

    protected $casts = [
        'payload'    => 'array',
        'is_active'  => 'boolean',
        'valid_from' => 'datetime',
        'valid_to'   => 'datetime',
    ];

    public function postMaster(): BelongsTo
    {
        return $this->belongsTo(FacebookPost::class, 'post_master_id');
    }

    public function syncRun(): BelongsTo
    {
        return $this->belongsTo(SyncRun::class, 'run_id');
    }
}
