<?php
// app/Http/Resources/PreviousProjectDetailResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\User;

class PreviousProjectDetailResource extends JsonResource
{
    public function toArray($request)
    {
        $team = $this->proposal?->team;
        
        // نجيب الـ members
        $members = [];
        if ($team && $team->members) {
            foreach ($team->members->where('status', 'active') as $member) {
                $user = User::find($member->student_user_id);
                $members[] = [
                    'id' => $member->student_user_id,
                    'name' => $user?->full_name ?? 'Unknown',
                    'role' => $member->role_in_team,
                ];
            }
        }

        return [
            'id' => $this->id,
            
            // Basic Info
            'title' => $this->proposal?->title,
            'description' => $this->proposal?->description,
            'image_url' => $this->proposal?->image_url,
            'attachment_file' => $this->proposal?->attachment_file,
            
            // Problem Statement
            'problem_statement' => $this->proposal?->problem_statement,
            
            // Year & Department
            'year' => $team?->academicYear?->code,
            'department' => $this->proposal?->department?->name,
            'project_type' => $this->proposal?->projectType?->name,
            
            // Technologies
            'technologies' => $this->proposal?->technologies 
                ? array_map('trim', explode(',', $this->proposal->technologies))
                : [],
            
            // Grade & Feedback
            'grade' => $this->final_score,
            'feedback' => [
                'text' => $this->feedback,
                'graded_by' => $this->graded_by,
                'graded_at' => $this->graded_at?->format('Y-m-d'),
            ],
            
            // Team Info
            'team' => [
                'id' => $team?->id,
                'leader_id' => $team?->leader_user_id,
                
                // Members
                'members' => $members,
                
                // Supervisors
                'supervisors' => $team?->supervisors
                    ? $team->supervisors
                        ->map(function($supervisor) {
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