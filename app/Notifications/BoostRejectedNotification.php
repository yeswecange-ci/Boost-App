<?php

namespace App\Notifications;

use App\Models\BoostRequest;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class BoostRejectedNotification extends Notification
{
    public function __construct(public BoostRequest $boost) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("❌ Votre boost #" . $this->boost->id . " a été rejeté")
            ->greeting("Bonjour " . $notifiable->name . ",")
            ->line("Votre demande de boost #" . $this->boost->id . " a été rejetée.")
            ->line("Raison : " . $this->boost->rejection_reason)
            ->action("Modifier et resoumettre", url('/boost/' . $this->boost->id));
    }

    public function toArray($notifiable): array
    {
        return [
            'boost_id'         => $this->boost->id,
            'type'             => 'boost_rejected',
            'message'          => "Votre boost #" . $this->boost->id . " a été rejeté",
            'rejection_reason' => $this->boost->rejection_reason,
        ];
    }
}