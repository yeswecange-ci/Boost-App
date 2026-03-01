<?php

namespace App\Notifications;

use App\Models\BoostRequest;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class BoostChangesRequestedNotification extends Notification
{
    public function __construct(public BoostRequest $boost) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("✏️ Modifications demandées sur votre boost #" . $this->boost->id)
            ->greeting("Bonjour " . $notifiable->name . ",")
            ->line("Des modifications ont été demandées sur votre boost #" . $this->boost->id . ".")
            ->line("Commentaire du validateur : " . $this->boost->rejection_reason)
            ->line("Effectuez les corrections demandées, puis resoumettez votre demande.")
            ->action("Voir et corriger le boost", route('boost.show', $this->boost->id));
    }

    public function toArray($notifiable): array
    {
        return [
            'boost_id' => $this->boost->id,
            'type'     => 'boost_changes_requested',
            'message'  => "Des modifications sont demandées sur votre boost #" . $this->boost->id,
            'comment'  => $this->boost->rejection_reason,
        ];
    }
}
