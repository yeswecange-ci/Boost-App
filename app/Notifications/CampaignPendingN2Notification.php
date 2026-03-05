<?php

namespace App\Notifications;

use App\Models\BoostCampaign;
use Illuminate\Notifications\Notification;

class CampaignPendingN2Notification extends Notification
{
    public function __construct(private BoostCampaign $campaign) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'boost_id' => $this->campaign->id,
            'message'  => "Campagne \"{$this->campaign->campaign_name}\" validée N+1 — en attente de votre validation N+2.",
        ];
    }
}
