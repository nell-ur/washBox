<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

class StaffNotificationController extends Controller
{
    public function index(Request $request)
    {
        $staff = Auth::guard('staff')->user();
        $notifications = $staff->notifications()->latest()->get();
        return response()->json($notifications);
    }

    public function unreadCount(Request $request)
    {
        $staff = Auth::guard('staff')->user();
        $count = $staff->notifications()->whereNull('read_at')->count();
        return response()->json(['unread_count' => $count]);
    }

    public function markAsRead($id)
    {
        $staff = Auth::guard('staff')->user();
        $notification = $staff->notifications()->findOrFail($id);
        $notification->update(['read_at' => now()]);
        return response()->json(['success' => true]);
    }

    public function markAllAsRead()
    {
        $staff = Auth::guard('staff')->user();
        $staff->notifications()->whereNull('read_at')->update(['read_at' => now()]);
        return response()->json(['success' => true]);
    }

    public function delete($id)
    {
        $staff = Auth::guard('staff')->user();
        $notification = $staff->notifications()->findOrFail($id);
        $notification->delete();
        return response()->json(['success' => true]);
    }

    public function clearRead()
    {
        $staff = Auth::guard('staff')->user();
        $staff->notifications()->whereNotNull('read_at')->delete();
        return response()->json(['success' => true]);
    }
}
