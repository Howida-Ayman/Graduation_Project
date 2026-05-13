<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewNotificationEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $notification;
    public $userId;

    public function __construct($notification, $userId)
    {
        $this->notification = $notification;
        $this->userId = $userId;
    }

    public function broadcastOn()
    {
        return new Channel('user.' . $this->userId);
    }

    public function broadcastAs()
    {
        return 'new-notification';
    }

    public function broadcastWith()
    {
        return [
            'id' => $this->notification->id,
            'type' => $this->notification->type,
            'data' => $this->notification->data,
            'read_at' => $this->notification->read_at,
            'created_at' => $this->notification->created_at->toDateTimeString(),
        ];
    }
}