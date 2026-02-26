<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoostRequest extends Model
{
    protected $fillable = [
        'post_id', 'page_id', 'page_name', 'post_url', 'post_thumbnail',
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
