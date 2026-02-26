<?php

namespace App\Notifications;

use App\Models\BoostRequest;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class BoostCreatedNotification extends Notification
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
            'type'     => 'boost_created',
            'message'  => "Boost #" . $this->boost->id . " — Campagne créée sur Meta, en attente d'activation",
        ];
    }
}
