<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoostCampaign extends Model
{
    protected $fillable = [
        'user_id',
        'campaign_name',
        'campaign_objective',
        'special_ad_categories',
        'campaign_status',
        'existing_campaign_id',
        'adset_name',
        'budget_type',
        'budget_value',
        'duration_days',
        'countries',
        'interests',
        'optimization_goal',
        'billing_event',
        'bid_strategy',
        'ad_name',
        'post_id',
        'ad_status',
        'execution_status',
        'meta_campaign_id',
        'meta_adset_id',
        'meta_ad_id',
        'error_message',
        'launched_at',
    ];

    protected $casts = [
        'countries'   => 'array',
        'interests'   => 'array',
        'launched_at' => 'datetime',
    ];

    // ── Relations ──────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function post()
    {
        return $this->belongsTo(FacebookPost::class, 'post_id', 'post_id');
    }

    // ── Accesseurs ─────────────────────────────────────────

    public function getStatusLabelAttribute(): string
    {
        return match($this->execution_status) {
            'draft'      => 'Brouillon',
            'pending_n1' => 'En attente N+1',
            'pending_n2' => 'En attente N+2',
            'approved'   => 'Approuvée',
            'rejected'   => 'Rejetée',
            'running'    => 'En cours…',
            'done'         => 'Créée sur Meta',
            'paused_ready' => 'Créée sur Meta (PAUSED)',
            'active'       => 'Active sur Meta',
            'error'        => 'Erreur',
            default      => ucfirst($this->execution_status),
        };
    }

    public function getStatusClassAttribute(): string
    {
        return match($this->execution_status) {
            'draft'      => 'badge-status-draft',
            'pending_n1' => 'badge-status-pending',
            'pending_n2' => 'badge-status-pending',
            'approved'   => 'badge-status-approved',
            'rejected'   => 'badge-status-rejected',
            'running'    => 'badge-status-pending',
            'done'         => 'badge-status-active',
            'paused_ready' => 'badge-status-created',
            'active'       => 'badge-status-active',
            'error'        => 'badge-status-rejected',
            default      => 'badge-status-draft',
        };
    }

    public function getBudgetFormattedAttribute(): string
    {
        return number_format($this->budget_value, 0, ',', ' ') . ' FCFA';
    }
}
