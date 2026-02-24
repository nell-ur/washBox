<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\StaffNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Display notifications page
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = StaffNotification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc');

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by read status (using read_at column)
        if ($request->filled('status')) {
            if ($request->status === 'unread') {
                $query->whereNull('read_at');
            } elseif ($request->status === 'read') {
                $query->whereNotNull('read_at');
            }
        }

        $notifications = $query->paginate(20);

        // Get counts for filters (using read_at)
        $counts = [
            'all' => StaffNotification::where('user_id', $user->id)->count(),
            'unread' => StaffNotification::where('user_id', $user->id)->whereNull('read_at')->count(),
            'read' => StaffNotification::where('user_id', $user->id)->whereNotNull('read_at')->count(),
        ];

        // Get notification types for filter
        $types = StaffNotification::where('user_id', $user->id)
            ->distinct()
            ->pluck('type')
            ->filter()
            ->values();

        return view('staff.notifications.index', compact('notifications', 'counts', 'types'));
    }

    /**
     * Get recent notifications (AJAX for dropdown)
     */
    public function getRecent(Request $request)
    {
        $user = Auth::user();
        $limit = $request->get('limit', 5);

        $notifications = StaffNotification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type ?? 'general',
                    'title' => $notification->title ?? 'Notification',
                    'message' => $notification->message ?? '',
                    'data' => $notification->data,
                    'is_read' => !is_null($notification->read_at), // read_at NOT NULL = read
                    'icon' => $notification->icon ?? $this->getNotificationIcon($notification->type),
                    'color' => $notification->color ?? $this->getNotificationColor($notification->type),
                    'link' => $notification->link,
                    'created_at' => $notification->created_at->diffForHumans(),
                    'created_at_formatted' => $notification->created_at->format('M j, Y g:i A'),
                ];
            });

        return response()->json([
            'success' => true,
            'notifications' => $notifications,
        ]);
    }

    /**
     * Get unread count (AJAX for badge)
     */
    public function getUnreadCount()
    {
        $user = Auth::user();

        $count = StaffNotification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'success' => true,
            'count' => $count,
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($id)
    {
        $user = Auth::user();

        $notification = StaffNotification::where('user_id', $user->id)
            ->findOrFail($id);

        $notification->update([
            'read_at' => now(),
        ]);

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read',
            ]);
        }

        return back()->with('success', 'Notification marked as read');
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        $user = Auth::user();

        StaffNotification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
            ]);

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'All notifications marked as read',
            ]);
        }

        return back()->with('success', 'All notifications marked as read');
    }

    /**
     * Delete a notification
     */
    public function destroy($id)
    {
        $user = Auth::user();

        $notification = StaffNotification::where('user_id', $user->id)
            ->findOrFail($id);

        $notification->delete();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Notification deleted',
            ]);
        }

        return back()->with('success', 'Notification deleted');
    }

    /**
     * Delete all read notifications
     */
    public function deleteAllRead()
    {
        $user = Auth::user();

        $deleted = StaffNotification::where('user_id', $user->id)
            ->whereNotNull('read_at')
            ->delete();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Deleted {$deleted} notifications",
                'deleted_count' => $deleted,
            ]);
        }

        return back()->with('success', "Deleted {$deleted} read notifications");
    }

    /**
     * Get notification icon based on type (fallback)
     */
    private function getNotificationIcon($type)
    {
        $icons = [
            'new_laundry' => 'bi-bag-plus',
            'laundry_received' => 'bi-bag-check',
            'laundry_ready' => 'bi-check-circle',
            'laundry_completed' => 'bi-trophy',
            'laundry_cancelled' => 'bi-x-circle',
            'laundry_assigned' => 'bi-person-badge',
            'washing_started' => 'bi-droplet',
            'payment_received' => 'bi-currency-dollar',
            'pickup_request' => 'bi-truck',
            'pickup_assigned' => 'bi-truck',
            'pickup_completed' => 'bi-box-seam',
            'unclaimed_laundry' => 'bi-clock',
            'urgent_unclaimed' => 'bi-exclamation-triangle',
            'system' => 'bi-gear',
            'announcement' => 'bi-megaphone',
        ];

        return $icons[$type] ?? 'bi-bell';
    }

    /**
     * Get notification color based on type (fallback)
     */
    private function getNotificationColor($type)
    {
        $colors = [
            'new_laundry' => 'info',
            'laundry_received' => 'primary',
            'laundry_ready' => 'info',
            'laundry_completed' => 'success',
            'laundry_cancelled' => 'danger',
            'laundry_assigned' => 'primary',
            'washing_started' => 'info',
            'payment_received' => 'success',
            'pickup_request' => 'info',
            'pickup_assigned' => 'primary',
            'pickup_completed' => 'success',
            'unclaimed_laundry' => 'warning',
            'urgent_unclaimed' => 'danger',
            'system' => 'secondary',
            'announcement' => 'primary',
        ];

        return $colors[$type] ?? 'secondary';
    }
}
