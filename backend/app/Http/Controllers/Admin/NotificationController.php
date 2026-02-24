<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Display all notifications
     */
    public function index(Request $request)
    {
        $query = AdminNotification::latest();

        // Filter by type
        if ($request->filled('type')) {
            $query->ofType($request->type);
        }

        // Filter by read status
        if ($request->filled('status')) {
            if ($request->status == 'unread') {
                $query->unread();
            } elseif ($request->status == 'read') {
                $query->read();
            }
        }

        $notifications = $query->paginate(20);

        // Stats
        $stats = [
            'total' => AdminNotification::count(),
            'unread' => AdminNotification::unread()->count(),
            'today' => AdminNotification::whereDate('created_at', today())->count(),
        ];

        // Notification types for filter
        $types = [
            'pickup_request' => 'Pickup Requests',
            'new_laundry' => 'New Laundries',
            'payment' => 'Payments',
            'laundry_completed' => 'Completed Laundries',
            'laundry_cancelled' => 'Cancelled Laundries',
            'unclaimed' => 'Unclaimed Laundry',
            'new_customer' => 'New Customers',
            'system' => 'System',
        ];

        return view('admin.notifications.index', compact('notifications', 'stats', 'types'));
    }

    /**
     * Get recent notifications for header dropdown (AJAX)
     */
    public function getRecent()
    {
        $notifications = AdminNotification::unread()
            ->latest()
            ->take(10)
            ->get();

        $unreadCount = AdminNotification::unread()->count();

        return response()->json([
            'notifications' => $notifications->map(function($n) {
                return [
                    'id' => $n->id,
                    'type' => $n->type,
                    'title' => $n->title,
                    'message' => $n->message,
                    'icon' => $n->icon_class,
                    'color' => $n->color,
                    'link' => $n->link,
                    'time_ago' => $n->time_ago,
                    'is_read' => $n->is_read,
                ];
            }),
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(AdminNotification $notification)
    {
        $notification->markAsRead();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Notification marked as read');
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        AdminNotification::unread()->update(['read_at' => now()]);

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'All notifications marked as read');
    }

    /**
     * Delete notification
     */
    public function destroy(AdminNotification $notification)
    {
        $notification->delete();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Notification deleted');
    }

    /**
     * Delete all read notifications
     */
    public function deleteAllRead()
    {
        AdminNotification::read()->delete();

        return back()->with('success', 'All read notifications deleted');
    }

    /**
     * Get unread count (AJAX)
     */
    public function getUnreadCount()
    {
        return response()->json([
            'count' => AdminNotification::unread()->count()
        ]);
    }
}
