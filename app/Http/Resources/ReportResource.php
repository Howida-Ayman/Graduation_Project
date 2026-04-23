<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subject' => $this->subject,
            'description' => $this->description,
            'attachment_url' => $this->attachment ? asset($this->attachment) : null,
            'status' => $this->status,
            'admin_response' => $this->admin_response,
            'created_at' => $this->created_at->toDateTimeString(),
            'resolved_at' => $this->resolved_at?->toDateTimeString(),
            'user' => [
                'id' => $this->user->id,
                'full_name' => $this->user->full_name,
                'email' => $this->user->email,
            ],
        ];
    }
}