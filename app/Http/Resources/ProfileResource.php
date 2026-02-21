<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'track_name' => $this->track_name,
            'profile_image_url' => $this->profile_image_url,

            'department' => $this->studentProfile?->department?->name,
            'academic_year' => $this->studentProfile?->academicYear?->code,
            'gpa' => $this->studentProfile?->gpa,
        ];
    }
}
