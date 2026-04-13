<?php

namespace App\Services;

use App\Models\Milestone;
use App\Models\Team;
use Illuminate\Support\Carbon;

class TeamDetailsService
{
    public function buildResponse(Team $team): array
    {
        $today = Carbon::now();

        $project = $team->graduationProject;
        $proposal = $project?->proposal;

        $doctor = $team->currentSupervisors
            ->firstWhere('pivot.supervisor_role', 'doctor');

        $ta = $team->currentSupervisors
            ->firstWhere('pivot.supervisor_role', 'ta');

        $members = $team->members->map(function ($member) {
            return [
                'id' => $member->student_user_id,
                'name' => $member->user?->full_name,
                'track_name' => $member->user?->track_name,
                'role_in_team' => $member->role_in_team,
                'image' => $member->user?->profile_image_url,
            ];
        })->values();

        $allMilestones = Milestone::orderBy('phase_number')->get();

        $teamMilestoneStatuses = $team->teamMilestonestatus->keyBy('milestone_id');

        $currentMilestone = $allMilestones->first(function ($milestone) use ($today) {
            return Carbon::parse($milestone->start_date) <= $today
                && Carbon::parse($milestone->deadline) >= $today;
        });

        if (! $currentMilestone) {
            $currentMilestone = $allMilestones->first();
        }

        $currentMilestoneStatus = $currentMilestone
            ? $teamMilestoneStatuses->get($currentMilestone->id)
            : null;

        $currentMilestoneSection = $currentMilestone ? [
            'id' => $currentMilestone->id,
            'title' => $currentMilestone->title,
            'deadline' => $currentMilestone->deadline,
            'team_status' => $currentMilestoneStatus?->status,
        ] : null;

        $teamProgressSteps = $allMilestones->map(function ($milestone) use ($teamMilestoneStatuses) {
            $teamStatusRow = $teamMilestoneStatuses->get($milestone->id);
            $teamStatus = $teamStatusRow?->status;

            $displayStatus = match ($milestone->status) {
                'completed' => 'completed',
                'on_progress' => 'in_progress',
                'pending' => 'locked',
                default => 'locked',
            };

            return [
                'id' => $milestone->id,
                'title' => $milestone->title,
                'phase_number' => $milestone->phase_number,
                'milestone_status' => $milestone->status,
                'team_status' => $teamStatus,
                'display_status' => $displayStatus,
            ];
        })->values();

        $totalCount = $allMilestones->count();

        $onTrackCount = $allMilestones->filter(function ($milestone) use ($teamMilestoneStatuses) {
            $row = $teamMilestoneStatuses->get($milestone->id);
            return $row && $row->status === 'on_track';
        })->count();

        $progressPercentage = $totalCount > 0
            ? round(($onTrackCount / $totalCount) * 100)
            : 0;

        $milestoneProgress = $allMilestones->map(function ($milestone) use ($teamMilestoneStatuses) {
            $row = $teamMilestoneStatuses->get($milestone->id);

            return [
                'id' => $milestone->id,
                'title' => $milestone->title,
                'team_status' => $row?->status,
                'milestone_grade' => $row?->milestone_grade,
            ];
        })->values();

        $submittedFiles = $team->submissions
            ->flatMap(function ($submission) {
                return $submission->files->map(function ($file) use ($submission) {
                    return [
                        'id' => $file->id,
                        'file_name' => $file->original_name,
                        'milestone' => $submission->milestone?->title,
                        'uploaded_at' => $file->uploaded_at ?? $file->created_at,
                    ];
                });
            })
            ->sortByDesc('uploaded_at')
            ->values();

        return [
            'message' => 'Team details retrieved successfully',

            'team' => [
                'id' => $team->id,
                'department' => [
                    'id' => $team->department?->id,
                    'name' => $team->department?->name,
                ],
                'members_count' => $members->count(),
                'members' => $members,
            ],

            'project' => [
                'title' => $proposal?->title,
                'description' => $proposal?->description,
                'problem_statement' => $proposal?->problem_statement,
                'solution' => $proposal?->solution,
                'image_url' => $project?->image_url ?? $proposal?->image_url,
                'file_url' => $proposal?->attachment_file,
                'category' => $proposal?->category,
                'technologies' => $proposal?->technologies,
            ],

            'supervisors' => [
                'doctor' => $doctor ? [
                    'id' => $doctor->id,
                    'name' => $doctor->full_name,
                ] : null,
                'ta' => $ta ? [
                    'id' => $ta->id,
                    'name' => $ta->full_name,
                ] : null,
            ],

            'current_milestone' => $currentMilestoneSection,

            'team_progress' => [
                'percentage' => $progressPercentage,
                'on_track_count' => $onTrackCount,
                'total_count' => $totalCount,
                'steps' => $teamProgressSteps,
            ],

            'milestone_progress' => $milestoneProgress,

            'submitted_files' => $submittedFiles,
        ];
    }
}