<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PreviousProjectDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $team = $this->proposal?->team;

        $members = [];
        if ($team && $team->members) {
            foreach ($team->members->where('status', 'active') as $member) {
                $user = $member->user;

                $members[] = [
                    'id' => $member->student_user_id,
                    'name' => $user?->full_name ?? 'Unknown',
                    'role' => $member->role_in_team,
                    'email' => $user?->email ?? 'Unknown',
                    'track' => $user?->track_name,
                    'department' => $user?->studentprofile?->department?->name,
                ];
            }
        }

        return [
            'id' => $this->id,

            'title' => $this->proposal?->title,
            'description' => $this->proposal?->description,
            'image_url' => $this->proposal?->image_url,
            'attachment_file' => $this->proposal?->attachment_file,

            'problem_statement' => $this->proposal?->problem_statement,

            'year' => $team?->academicYear?->code,
            'department' => $this->proposal?->department?->name,
            'project_type' => $this->proposal?->projectType?->name,

            'technologies' => $this->proposal?->technologies
                ? collect(explode(',', $this->proposal->technologies))
                    ->map(fn($item) => trim($item))
                    ->filter()
                    ->values()
                : [],

            'grade' => $this->final_score,
            'feedback' => [
                'text' => $this->feedback,
                'graded_by' => $this->graded_by,
                'graded_at' => $this->graded_at?->format('Y-m-d'),
            ],

            'team' => [
                'id' => $team?->id,
                'leader_id' => $team?->leader_user_id,

                'members' => $members,

                'supervisors' => $team?->supervisors
                    ? $team->supervisors->map(function ($supervisor) {
                        return [
                            'id' => $supervisor->id,
                            'name' => $supervisor->full_name,
                            'role' => $supervisor->pivot->supervisor_role,
                            'assigned_at' => $supervisor->pivot->assigned_at,
                        ];
                    })->values()
                    : [],
            ],
        ];
    }
}