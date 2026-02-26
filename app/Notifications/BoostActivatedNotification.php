<?php

namespace App\Notifications;

use App\Models\BoostRequest;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class BoostActivatedNotification extends Notification
{
    public function __construct(public BoostRequest $boost) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'boost_id' => $this->boost->id,
            'type'     => 'boost_activated',
            'message'  => "Boost #" . $this->boost->id . " — Campagne activée avec succès sur Meta !",
        ];
    }
}
