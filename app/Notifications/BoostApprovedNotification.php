<?php

namespace App\Notifications;

use App\Models\BoostRequest;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class BoostApprovedNotification extends Notification
{
    public function __construct(public BoostRequest $boost) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("✅ Votre boost #" . $this->boost->id . " a été approuvé")
            ->greeting("Bonjour " . $notifiable->name . ",")
            ->line("Votre demande de boost #" . $this->boost->id . " a été approuvée.")
            ->line("La campagne est en cours de création sur Meta Ads.")
            ->action("Voir le boost", route('boost.show', $this->boost->id));
    }

    public function toArray($notifiable): array
    {
        return [
            'boost_id' => $this->boost->id,
            'type'     => 'boost_approved',
            'message'  => "Votre boost #" . $this->boost->id . " a été approuvé",
        ];
    }
}