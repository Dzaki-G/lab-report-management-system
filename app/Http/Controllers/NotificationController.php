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
        if (isset($notification->data['form_id'])) {
            $userRole = auth()->user()->role_id;
            
            // Determine route based on role
            if ($userRole == \App\Enums\Role::ADMIN) {
                return redirect()->route('form.show', $notification->data['form_id']);
            } elseif ($userRole == \App\Enums\Role::KEPALA_UPA) {
                return redirect()->route('kepala-upa.show', $notification->data['form_id']);
            } elseif ($userRole == \App\Enums\Role::KEPALA_DIVISI) {
                return redirect()->route('kepala-divisi.show', $notification->data['form_id']);
            } elseif ($userRole == \App\Enums\Role::ANALIS) {
                return redirect()->route('analis.form.show', $notification->data['form_id']);
            }
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
