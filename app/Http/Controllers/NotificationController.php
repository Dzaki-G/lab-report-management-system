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
     * Mark single notification as read and redirect to related form
     */
    public function markAsRead($notif)
    {
        // Only find notification that belongs to the current user (prevents 403)
        $notification = Notification::where('id', $notif)
            ->where('user_id', auth()->user()->user_id)
            ->first();

        // If not found or doesn't belong to this user, redirect gracefully
        if (!$notification) {
            return redirect()->route('notifications.index')
                ->with('error', 'Notifikasi tidak ditemukan.');
        }

        $notification->update(['is_read' => true]);

        // Redirect to related form if exists
        // Use form-list.show which is accessible by ALL roles (avoids 403)
        if (isset($notification->data['form_id'])) {
            $formId = $notification->data['form_id'];
            // Check if the form still exists before redirecting
            if (\App\Models\FormPengujian::find($formId)) {
                return redirect()->route('form-list.show', $formId);
            }
        }

        return redirect()->route('notifications.index');
    }

    /**
     * Delete a notification
     */
    public function destroy($notif)
    {
        $notification = Notification::where('id', $notif)
            ->where('user_id', auth()->user()->user_id)
            ->first();

        if (!$notification) {
            return redirect()->route('notifications.index')
                ->with('error', 'Notifikasi tidak ditemukan.');
        }

        $notification->delete();

        return back()->with('success', 'Notifikasi berhasil dihapus.');
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
