<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    /**
     * Get all notifications for authenticated customer
     */
    public function index(Request $request)
    {
        try {
            $customer = $request->user(); // Get authenticated customer

            // Query REAL database notifications table
            $query = Notification::where('customer_id', $customer->id)
                ->orderBy('created_at', 'desc');

            // Filter by unread if requested
            if ($request->query('unread_only') === 'true') {
                $query->where('is_read', false);
            }

            $notifications = $query->limit(50)->get();

            Log::info('Fetched notifications', [
                'customer_id' => $customer->id,
                'count' => $notifications->count(),
                'unread_only' => $request->query('unread_only')
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'notifications' => $notifications,
                    'total' => $notifications->count(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching notifications: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get unread notification count - QUERIES REAL DATABASE
     */
    public function unreadCount(Request $request)
    {
        try {
            $customer = $request->user(); // Get authenticated customer

            // COUNT unread notifications from REAL database table
            $unreadCount = Notification::where('customer_id', $customer->id)
                ->where('is_read', false)
                ->count();

            Log::info('Unread count fetched', [
                'customer_id' => $customer->id,
                'unread_count' => $unreadCount
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'unread_count' => $unreadCount
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching unread count: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch unread count',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Request $request, $id)
    {
        try {
            $customer = $request->user();

            // Find notification in database
            $notification = Notification::where('id', $id)
                ->where('customer_id', $customer->id)
                ->firstOrFail();

            // Update in database
            $notification->is_read = true;
            $notification->read_at = now();
            $notification->save();

            Log::info('Notification marked as read', [
                'notification_id' => $id,
                'customer_id' => $customer->id
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'notification' => $notification
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error marking notification as read: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notification as read',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request)
    {
        try {
            $customer = $request->user();

            // Update ALL unread notifications in database
            $updated = Notification::where('customer_id', $customer->id)
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => now()
                ]);

            Log::info('All notifications marked as read', [
                'customer_id' => $customer->id,
                'updated_count' => $updated
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'updated_count' => $updated
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error marking all as read: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark all as read',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete notification
     */
    public function destroy(Request $request, $id)
    {
        try {
            $customer = $request->user();

            // Find and delete from database
            $notification = Notification::where('id', $id)
                ->where('customer_id', $customer->id)
                ->firstOrFail();

            $notification->delete();

            Log::info('Notification deleted', [
                'notification_id' => $id,
                'customer_id' => $customer->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Notification deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting notification: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear all read notifications
     */
    public function clearRead(Request $request)
    {
        try {
            $customer = $request->user();

            // Delete all read notifications from database
            $deleted = Notification::where('customer_id', $customer->id)
                ->where('is_read', true)
                ->delete();

            Log::info('Read notifications cleared', [
                'customer_id' => $customer->id,
                'deleted_count' => $deleted
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'deleted_count' => $deleted
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error clearing read notifications: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to clear notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
