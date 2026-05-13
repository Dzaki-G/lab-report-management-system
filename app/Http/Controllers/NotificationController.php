<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get user's notifications for navbar dropdown
     */
    public function getNotifications()
    {
        $notifications = Notification::forUser(auth()->user()->user_id)
            ->latest()
            ->take(10)
            ->get();

        $unreadCount = Notification::forUser(auth()->user()->user_id)
            ->unread()
            ->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Display all notifications page
     */
    public function index()
    {
        $notifications = Notification::forUser(auth()->user()->user_id)
            ->latest()
            ->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    /**
     * Mark single notification as read
     */
    public function markAsRead($notif)
    {
        $notification = Notification::findOrFail($notif);

        if ($notification->user_id !== auth()->user()->user_id) {
            abort(403);
        }

        $notification->update(['is_read' => true]);

        // Redirect to related form if exists
        // Use form-list.show which is accessible by ALL roles (avoids 403)
        if (isset($notification->data['form_id'])) {
            return redirect()->route('form-list.show', $notification->data['form_id']);
        }

        return redirect()->route('notifications.index');
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        Notification::forUser(auth()->user()->user_id)
            ->unread()
            ->update(['is_read' => true]);

        return back()->with('success', 'Semua notifikasi telah ditandai sudah dibaca');
    }
}
