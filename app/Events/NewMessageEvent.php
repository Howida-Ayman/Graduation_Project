<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewMessageEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $conversationId;

    public function __construct(Message $message, $conversationId)
    {
        $this->message = $message;
        $this->conversationId = $conversationId;
    }

    public function broadcastOn()
    {
        return new Channel('conversation.' . $this->conversationId);
    }

    public function broadcastAs()
    {
        return 'new-message';
    }

    public function broadcastWith()
    {
        return [
            'id' => $this->message->id,
            'message' => $this->message->message,
            'type' => $this->message->type,
            'file_url' => $this->message->file_url ? asset($this->message->file_url) : null,
            'sender' => [
                'id' => $this->message->sender->id,
                'name' => $this->message->sender->full_name,
                'profile_image_url' => $this->message->sender->profile_image_url ? asset($this->message->sender->profile_image_url) : null,
            ],
            'created_at' => $this->message->created_at->toDateTimeString(),
        ];
    }
}