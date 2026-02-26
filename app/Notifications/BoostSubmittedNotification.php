<?php

namespace App\Notifications;

use App\Models\BoostRequest;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class BoostSubmittedNotification extends Notification
{
    public function __construct(public BoostRequest $boost) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Nouvelle demande de boost #" . $this->boost->id . " à valider")
            ->greeting("Bonjour " . $notifiable->name . ",")
            ->line("Une nouvelle demande de boost a été soumise par " . $this->boost->operator->name . ".")
            ->line("Page : " . $this->boost->page_name)
            ->line("Budget : " . number_format($this->boost->budget, 0, ',', ' ') . " " . $this->boost->currency)
            ->line("Période : " . $this->boost->start_date->format('d/m/Y') . " → " . $this->boost->end_date->format('d/m/Y'))
            ->action("Voir la demande", route('boost.pending-n1'))
            ->line("Merci de valider ou rejeter cette demande.");
    }

    public function toArray($notifiable): array
    {
        return [
            'boost_id'   => $this->boost->id,
            'type'       => 'boost_submitted',
            'message'    => "Nouvelle demande de boost #" . $this->boost->id . " à valider",
            'page_name'  => $this->boost->page_name,
            'operator'   => $this->boost->operator->name,
        ];
    }
}