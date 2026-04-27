// app/Http/Resources/MessageResource.php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'message' => $this->message,
            'type' => $this->type,
            'file_url' => $this->file_url ? asset($this->file_url) : null,
            'is_read' => $this->is_read,
            'sender' => new UserResource($this->whenLoaded('sender')),
            'created_at' => $this->created_at->toDateTimeString(),
            'is_mine' => $this->sender_user_id == $request->user()?->id,
        ];
    }
}