<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BoostRequest extends Model
{
    protected $fillable = [
        'post_id', 'post_master_id', 'page_id', 'page_name', 'post_url', 'post_thumbnail',
        'post_message', 'start_date', 'end_date', 'budget', 'currency',
        'sensitivity', 'whatsapp_url',
        'target', 'status', 'operator_id', 'validator_id', 'rejection_reason',
        'meta_campaign_id', 'meta_adset_id', 'meta_ad_id',
        'n8n_payload', 'n8n_response',
    ];

    protected $casts = [
        'target'       => 'array',
        'n8n_payload'  => 'array',
        'n8n_response' => 'array',
        'start_date'   => 'date',
        'end_date'     => 'date',
    ];

    // Relations
    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function validator()
    {
        return $this->belongsTo(User::class, 'validator_id');
    }

    public function approvals()
    {
        return $this->hasMany(Approval::class)->with('user')->orderBy('created_at');
    }

    public function analytics()
    {
        return $this->hasMany(BoostAnalytics::class);
    }

    public function postMaster()
    {
        return $this->belongsTo(FacebookPost::class, 'post_master_id');
    }

    public function boostRun(): HasOne
    {
        return $this->hasOne(BoostRun::class, 'boost_request_id');
    }

    // ── Accesseurs ─────────────────────────────────────────

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft'        => 'Brouillon',
            'pending_n1'   => 'En attente N+1',
            'pending_n2'   => 'En attente N+2',
            'approved'     => 'Approuvé',
            'rejected_n1'  => 'Rejeté N+1',
            'rejected_n2'  => 'Rejeté N+2',
            'paused_ready' => 'Prêt à lancer',
            'active'       => 'Actif',
            'done'         => 'Terminé',
            'error'        => 'Erreur',
            default        => ucfirst($this->status),
        };
    }

    public function getStatusClassAttribute(): string
    {
        return match($this->status) {
            'draft'        => 'badge-status-draft',
            'pending_n1'   => 'badge-status-pending',
            'pending_n2'   => 'badge-status-pending',
            'approved'     => 'badge-status-approved',
            'rejected_n1'  => 'badge-status-rejected',
            'rejected_n2'  => 'badge-status-rejected',
            'paused_ready' => 'badge-status-paused',
            'active'       => 'badge-status-active',
            'done'         => 'badge-status-created',
            'error'        => 'badge-status-rejected',
            default        => 'badge-status-draft',
        };
    }

    public function getBudgetFormattedAttribute(): string
    {
        return '$' . number_format((float) $this->budget, 2, '.', ',');
    }

    // Helpers de statut
    public function isPendingN1(): bool   { return $this->status === 'pending_n1'; }
    public function isPendingN2(): bool   { return $this->status === 'pending_n2'; }
    public function isPending(): bool     { return in_array($this->status, ['pending_n1', 'pending_n2']); }
    public function isApproved(): bool    { return $this->status === 'approved'; }
    public function isPausedReady(): bool { return $this->status === 'paused_ready'; }
    public function isActive(): bool      { return $this->status === 'active'; }
    public function isRejected(): bool    { return in_array($this->status, ['rejected_n1', 'rejected_n2']); }
    public function needsN2(): bool       { return in_array($this->sensitivity, ['moyenne', 'elevee']); }
}
