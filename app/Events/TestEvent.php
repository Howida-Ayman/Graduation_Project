<?php

namespace App\Events;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class TestEvent implements ShouldBroadcast
{
    public $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('test-channel'),
        ];
    }
    public function broadcastWith(): array
{
    \Log::info('Broadcast sent successfully');

    return [
        'message' => $this->message,
    ];
}
}
