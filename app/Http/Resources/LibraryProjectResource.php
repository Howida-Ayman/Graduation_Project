<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\SuggestedProject;
use App\Models\PreviousProject;

class LibraryProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof SuggestedProject) {
            return [
                'id' => $this->id,
                'type' => 'suggested',
                'title' => $this->title,
                'description' => $this->description,

                'department_id' => $this->department?->id,
                'department_name' => $this->department?->name,

                'year' => null,

                'technologies' => $this->recommended_tools
                    ? collect(explode(',', $this->recommended_tools))
                        ->map(fn($item) => trim($item))
                        ->filter()
                        ->values()
                    : [],

                'favorites' => $this->favorites_count ?? 0,
            ];
        }

        if ($this->resource instanceof PreviousProject) {
            return [
                'id' => $this->id,
                'type' => 'previous',
                'title' => $this->proposal?->title,
                'description' => $this->proposal?->description,

                'department_id' => $this->proposal?->department?->id,
                'department_name' => $this->proposal?->department?->name,

                'year' => $this->proposal?->team?->academicYear?->code,

                'technologies' => $this->proposal?->technologies
                    ? collect(explode(',', $this->proposal->technologies))
                        ->map(fn($item) => trim($item))
                        ->filter()
                        ->values()
                    : [],

                'favorites' => $this->favorites_count ?? 0,
            ];
        }

        

        return [];
    }
}