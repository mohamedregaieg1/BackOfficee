<?php

namespace App\Events;

use App\Models\Notification;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewNotificationEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $notification;

    // On passe une seule notification dans le constructeur
    public function __construct(Notification $notification)
    {
        $this->notification = $notification;
    }

    // Diffuser sur un canal public ou privé en fonction des besoins
    public function broadcastOn()
    {
        // Canal privé pour l'utilisateur récepteur
        return new Channel('notifications.' . $this->notification->receiver_id); 
    }

    // Nom de l'événement émis
    public function broadcastAs()
    {
        return 'new-notification';
    }
}
