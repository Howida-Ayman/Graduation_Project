<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'team_id' => $this->team_id,
            'participants' => ParticipantResource::collection($this->whenLoaded('participants')),
            'messages' => MessageResource::collection($this->whenLoaded('messages')),
            'last_message' => new MessageResource($this->messages()->latest()->first()),
            'unread_count' => $this->when($request->user(), 
                $this->getUnreadCountForUser($request->user()->id)
            ),
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}