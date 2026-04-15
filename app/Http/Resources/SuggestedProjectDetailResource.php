<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SuggestedProjectDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'title' => $this->title,
            'description' => $this->description,
            'image_url' => $this->image_url,

            'department_id' => $this->department?->id,
            'department_name' => $this->department?->name,

            'technologies' => $this->recommended_tools
                ? collect(explode(',', $this->recommended_tools))
                    ->map(fn($item) => trim($item))
                    ->filter()
                    ->values()
                : [],

            'created_at' => $this->created_at?->format('Y-m-d'),
            'updated_at' => $this->updated_at?->format('Y-m-d'),
        ];
    }
}