<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LibraryProjectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
   
        public function toArray($request)
    {
        // لو Suggested
        if ($this->resource instanceof \App\Models\SuggestedProject) {

            return [
                'id' => $this->id,
                'type' => 'suggested',
                'title' => $this->title,
                'description' => $this->description,
                'year' => null,
                'department' => $this->department?->name,
                'technologies' => $this->recommended_tools,
                // 'views' => $this->views_count,
                'favorites' => $this->favorites_count,
            ];
        }

        // لو Previous
        if ($this->resource instanceof \App\Models\PreviousProject) {

            return [
                'id' => $this->id,
                'type' => 'previous',
                'title' => $this->proposal?->title,
                'description' => $this->proposal?->description,
                'year' => $this->proposal?->team?->academicYear?->code,
                'department' => $this->proposal?->department?->name,
                'technologies' => $this->proposal?->technologies,
                // 'views' => $this->proposal?->views_count,
                'favorites' => $this->favorites_count,
            ];
        }

        return [];
    }
    }

