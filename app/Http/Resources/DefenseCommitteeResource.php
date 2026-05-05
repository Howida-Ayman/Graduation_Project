<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DefenseCommitteeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
                return [
            'id' => $this->id,
            'team_id' => $this->team_id,

            'project_title' => $this->team?->graduationProject?->proposal?->title,
            'project_category' => $this->team?->graduationProject?->proposal?->category,

            'scheduled_at' => $this->scheduled_at,

            'doctors' => $this->members
                ->where('member_role', 'doctor')
                ->map(fn($d) => [
                    'id' => $d->member?->id,
                    'name' => $d->member?->full_name,
                ])->values(),

            'assistant' => $this->members
                ->where('member_role', 'ta')
                ->map(fn($d) => [
                    'id' => $d->member?->id,
                    'name' => $d->member?->full_name,
                ])->values(),

            'grade' => $this->grade?->grade,
        ];
    }
}
