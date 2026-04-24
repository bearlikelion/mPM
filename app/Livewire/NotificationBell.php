<?php

namespace App\Livewire;

use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class NotificationBell extends Component
{
    public function markAsRead(string $notificationId): void
    {
        $notification = Auth::user()
            ->notifications()
            ->whereKey($notificationId)
            ->first();

        $notification?->markAsRead();
    }

    public function markAllAsRead(): void
    {
        Auth::user()->unreadNotifications->markAsRead();
    }

    public function refreshBell(): void
    {
        //
    }

    public function render()
    {
        return view('livewire.notification-bell', [
            'unreadCount' => Auth::user()->unreadNotifications()->count(),
            'notifications' => Auth::user()
                ->notifications()
                ->latest()
                ->limit(8)
                ->get()
                ->map(fn (DatabaseNotification $notification): array => [
                    'id' => $notification->id,
                    'title' => $notification->data['title'] ?? 'Notification',
                    'body' => $notification->data['body'] ?? '',
                    'url' => $notification->data['url'] ?? '#',
                    'read' => $notification->read_at !== null,
                    'created' => $notification->created_at?->diffForHumans(),
                ]),
        ]);
    }
}
