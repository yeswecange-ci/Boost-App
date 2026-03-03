<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostHistory extends Model
{
    protected $table = 'posts_history';

    protected $fillable = [
        'post_master_id',
        'type',
        'message',
        'permalink_url',
        'created_time',
        'full_picture',
        'link_url',
        'payload',
        'row_hash',
        'run_id',
        'is_active',
        'valid_from',
        'valid_to',
    ];

    protected $casts = [
        'payload'      => 'array',
        'is_active'    => 'boolean',
        'created_time' => 'datetime',
        'valid_from'   => 'datetime',
        'valid_to'     => 'datetime',
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
