<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function markRead(string $id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $data = $notification->data;

        if (!empty($data['campaign_id'])) {
            return redirect()->route('campaigns.show', $data['campaign_id']);
        }

        if (!empty($data['boost_id'])) {
            return redirect()->route('boost.show', $data['boost_id']);
        }

        return redirect()->route('home');
    }

    public function markAllRead()
    {
        auth()->user()->unreadNotifications->markAsRead();
        return redirect()->back()->with('success', 'Toutes les notifications ont été lues.');
    }
}