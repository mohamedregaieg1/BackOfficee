<?php

namespace App\Events;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewNotificationEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $notification;
    public $senderAvatarPath;

    public function __construct(Notification $notification)
    {
        $this->notification = $notification;

        $sender = User::find($notification->sender_id);
        $this->senderAvatarPath = $sender->avatar_path;
    }

    public function broadcastOn()
    {
        return new Channel('notifications-channel.' . $this->notification->receiver_id);
    }

    public function broadcastAs()
    {
        return 'new-notification';
    }

    public function broadcastWith()
    {
        return [
            'notification' => $this->notification,
            'sender_avatar_path' => $this->senderAvatarPath,
        ];
    }
}
