<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function analytics(): HasMany
    {
        return $this->hasMany(CampaignAnalytics::class, 'boost_campaign_id')->orderBy('date_snapshot');
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
        return '$' . number_format($this->budget_value, 2, '.', ',');
    }

    /**
     * Totaux cumulés calculés depuis les analytics chargées.
     * Appeler après $campaign->load('analytics').
     */
    public function getAnalyticsTotalsAttribute(): array
    {
        $rows = $this->relationLoaded('analytics') ? $this->analytics : collect();

        return [
            'impressions' => $rows->sum('impressions'),
            'reach'       => $rows->sum('reach'),       // approximatif (somme des jours)
            'clicks'      => $rows->sum('clicks'),
            'spend'       => round($rows->sum('spend'), 2),
            'ctr'         => $rows->avg('ctr')  ?? 0,
            'cpm'         => $rows->avg('cpm')  ?? 0,
            'cpc'         => $rows->avg('cpc')  ?? 0,
            'days'        => $rows->count(),
        ];
    }
}
