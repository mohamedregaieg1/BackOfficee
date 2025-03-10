<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $notifications = Notification::where('receiver_id', $user->id)
                            ->orderBy('created_at', 'desc')
                            ->get();

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
}
