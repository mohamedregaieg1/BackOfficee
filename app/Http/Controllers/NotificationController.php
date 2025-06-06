<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;
use App\Events\NewNotificationEvent;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $user = Auth::user();
    
        $notifications = Notification::select(
            'notifications.*',
            'users.avatar_path as sender_avatar_path',
            'users.gender as sender_gender'
        )
        ->join('users', 'notifications.sender_id', '=', 'users.id')
        ->where('notifications.receiver_id', $user->id)
        ->orderBy('notifications.created_at', 'desc')
        ->get();
    
        foreach ($notifications as $notification) {
            if ($notification->sender_avatar_path) {
                if (filter_var($notification->sender_avatar_path, FILTER_VALIDATE_URL)) {
                    $notification->sender_avatar_path = $notification->sender_avatar_path;
                } else {
                    $notification->sender_avatar_path = asset('storage/avatars/' . basename($notification->sender_avatar_path));
                }
            }
        }
    
        return response()->json($notifications);
    }

    public function markAsRead($id)
    {
        $notification = Notification::where('id', $id)
                            ->where('receiver_id', Auth::id())
                            ->firstOrFail();

        $notification->update(['is_read' => true]);

        return response()->json(['message' => 'Notification marked as read.']);
    }

    public function unreadCount()
    {
        $count = Notification::where('receiver_id', Auth::id())
                        ->where('is_read', false)
                        ->count();

        return response()->json(['unread_count' => $count]);
    }

    public function deleteNotification($id)
    {
        try {
            $notification = Notification::where('id', $id)
                                        ->where('receiver_id', Auth::id())
                                        ->firstOrFail();

            $notification->delete();

            return response()->json(['message' => 'Notification deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An error occurred while deleting the notification.',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
