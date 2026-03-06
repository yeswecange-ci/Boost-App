<?php

namespace App\Notifications;

use App\Models\BoostCampaign;
use Illuminate\Notifications\Notification;

class CampaignSubmittedNotification extends Notification
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
            'type'     => 'campaign',
            'message'  => "Campagne \"{$this->campaign->campaign_name}\" soumise par {$this->campaign->user?->name} — en attente de validation N+1.",
        ];
    }
}
