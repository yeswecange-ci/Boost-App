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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->execution_status) {
            'pending' => 'En attente',
            'running' => 'En cours…',
            'done'    => 'Créée',
            'error'   => 'Erreur',
            default   => ucfirst($this->execution_status),
        };
    }

    public function getStatusClassAttribute(): string
    {
        return match($this->execution_status) {
            'pending' => 'badge-status-pending',
            'running' => 'badge-status-pending',
            'done'    => 'badge-status-active',
            'error'   => 'badge-status-rejected',
            default   => 'badge-status-draft',
        };
    }

    public function getBudgetFormattedAttribute(): string
    {
        return number_format($this->budget_value, 0, ',', ' ') . ' FCFA';
    }
}
