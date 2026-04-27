<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'national_id' => $this->national_id,
            'track_name' => $this->track_name,
            'profile_image_url' => $this->profile_image_url ? asset($this->profile_image_url) : null,
            'role' => [
                'id' => $this->role?->id,
                'name' => $this->role?->name,
                'code' => $this->role?->code,
            ],
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}