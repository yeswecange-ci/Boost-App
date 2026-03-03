<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncError extends Model
{
    // Seulement created_at, pas de updated_at
    const UPDATED_AT = null;

    protected $fillable = [
        'run_id',
        'post_id',
        'step',
        'error_code',
        'error_message',
        'payload',
    ];

    protected $casts = [
        'payload'    => 'array',
        'created_at' => 'datetime',
    ];

    public function syncRun(): BelongsTo
    {
        return $this->belongsTo(SyncRun::class, 'run_id');
    }
}
