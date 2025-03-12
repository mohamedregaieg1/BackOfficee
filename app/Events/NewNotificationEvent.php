<?php

namespace App\Events;

use App\Models\Notification;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
class NewNotificationEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    
    public $notification;

    public function __construct(Notification $notifications)
    {
        $this->notification = $notifications;
        Log::info('NewNotificationEvent Fired:', ['notification' => $notifications]);
    }

    public function broadcastOn()
    {
        return ['notifications-channel'];
    }

    public function broadcastAs()
    {
        return 'new-notification';
    }
    
}