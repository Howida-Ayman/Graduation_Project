<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParticipantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'role' => $this->role,
            'joined_at' => $this->joined_at?->toDateTimeString(),
            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}