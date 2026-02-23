<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SuggestedProjectDetailResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            
            // Basic Info
            'title' => $this->title,
            'description' => $this->description,
            'image_url' => $this->image_url,
            
            // Problem Statement (لو موجود)
            //'problem_statement' => $this->problem_statement,
            
            // Department
            'department' => $this->department?->name,
            
            // Technologies
            'technologies' => $this->recommended_tools 
                ? array_map('trim', explode(',', $this->recommended_tools))
                : [],
            
       
            
            // Timestamps
            'created_at' => $this->created_at?->format('Y-m-d'),
        ];
    }
}