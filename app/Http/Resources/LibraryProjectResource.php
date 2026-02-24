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
                'department' => $this->department?->name,
                'technologies' => $this->recommended_tools 
                ? array_map('trim', explode(',', $this->recommended_tools))
                : [],  // تحويل النص إلى array
                'favorites' => $this->favorites_count ?? 0,
                // 'views' => $this->views_count,
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
                'technologies' => $this->proposal?->technologies
                ? array_map('trim', explode(',', $this->proposal->technologies))
                : [],  // تحويل النص إلى array
                'favorites' => $this->favorites_count ?? 0,
                // 'views' => $this->proposal?->views_count,
                
            ];
        }

        return [];
    }
    }

