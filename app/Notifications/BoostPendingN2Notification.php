<?php

namespace App\Notifications;

use App\Models\BoostRequest;
use Illuminate\Notifications\Notification;

class BoostPendingN2Notification extends Notification
{
    public function __construct(private BoostRequest $boost) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'boost_id' => $this->boost->id,
            'message'  => "Boost #" . $this->boost->id . " — validé N+1, en attente de votre validation N+2 (sensibilité : {$this->boost->sensitivity}).",
        ];
    }
}
