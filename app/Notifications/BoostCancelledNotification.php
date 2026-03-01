<?php

namespace App\Notifications;

use App\Models\BoostRequest;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class BoostCancelledNotification extends Notification
{
    public function __construct(public BoostRequest $boost) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("🚫 Votre boost #" . $this->boost->id . " a été annulé")
            ->greeting("Bonjour " . $notifiable->name . ",")
            ->line("Votre boost #" . $this->boost->id . " a été annulé par un validateur.")
            ->when($this->boost->rejection_reason, fn($mail) =>
                $mail->line("Motif : " . $this->boost->rejection_reason)
            )
            ->action("Voir le boost", route('boost.show', $this->boost->id));
    }

    public function toArray($notifiable): array
    {
        return [
            'boost_id' => $this->boost->id,
            'type'     => 'boost_cancelled',
            'message'  => "Votre boost #" . $this->boost->id . " a été annulé",
            'reason'   => $this->boost->rejection_reason,
        ];
    }
}
