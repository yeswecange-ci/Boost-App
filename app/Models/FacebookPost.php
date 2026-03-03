<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FacebookPost extends Model
{
    // La table a été renommée facebook_posts → posts_master (migration 100002)
    protected $table = 'posts_master';

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
        // Champs SCD2 / boostabilité (PDF Architecture)
        'fb_status',
        'fb_last_checked_at',
        'fb_last_error',
        'business_status',
        'is_boostable',
        'last_sync_run_id',
    ];

    protected $casts = [
        'impressions'         => 'integer',
        'is_boostable'        => 'boolean',
        'posted_at'           => 'datetime',
        'last_synced_at'      => 'datetime',
        'fb_last_checked_at'  => 'datetime',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(FacebookPage::class, 'facebook_page_id');
    }

    public function lastSyncRun(): BelongsTo
    {
        return $this->belongsTo(SyncRun::class, 'last_sync_run_id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(PostHistory::class, 'post_master_id');
    }

    public function mediaHistory(): HasMany
    {
        return $this->hasMany(PostMediaHistory::class, 'post_master_id');
    }

    public function boostRuns(): HasMany
    {
        return $this->hasMany(BoostRun::class, 'post_master_id');
    }

    // ─────────────────────────────────────────────────────────
    // Règle de boostabilité (PDF Architecture — stricte)
    // Un post est boostable si et seulement si :
    //   fb_status = FB_OK AND business_status = ACTIVE AND is_boostable = 1
    // ─────────────────────────────────────────────────────────

    public function isBoostable(): bool
    {
        return $this->fb_status === 'FB_OK'
            && $this->business_status === 'ACTIVE'
            && (bool) $this->is_boostable;
    }

    public function getBoostabilityReasonAttribute(): string
    {
        if ($this->fb_status !== 'FB_OK') {
            return match ($this->fb_status) {
                'FB_DELETED_OR_UNAVAILABLE' => 'Post supprimé ou inaccessible sur Facebook',
                'FB_ERROR'                  => 'Erreur lors de la vérification Facebook',
                default                     => 'Statut Facebook inconnu',
            };
        }
        if ($this->business_status !== 'ACTIVE') {
            return match ($this->business_status) {
                'INACTIVE' => 'Post désactivé manuellement',
                'ARCHIVED' => 'Post archivé',
                default    => 'Statut métier non actif',
            };
        }
        if (!$this->is_boostable) {
            return 'Post marqué comme non boostable';
        }
        return 'Boostable';
    }

    // ─────────────────────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────────────────────

    public function scopeBoostable($query)
    {
        return $query->where('fb_status', 'FB_OK')
                     ->where('business_status', 'ACTIVE')
                     ->where('is_boostable', 1);
    }

    public function scopeFbOk($query)
    {
        return $query->where('fb_status', 'FB_OK');
    }
}
