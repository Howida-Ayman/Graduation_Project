<?php

namespace App\Http\Controllers\Api\Team;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\DatabaseNotification;
use App\Models\ProjectCourse;
use App\Models\ProjectRule;
use App\Models\Proposal;
use App\Models\TeamMembership;
use App\Models\TeamNote;
use App\Models\User;
use App\Notifications\TeamNoteNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeamController extends Controller
{
    private function hasActiveCapstoneEnrollment($user, $academicYear): bool
{
    return $user->enrollments()
        ->where('academic_year_id', $academicYear->id)
        ->where('status', 'in_progress')
        ->whereHas('projectCourse', function ($q) {
            $q->whereIn('order', [1, 2]);
        })
        ->exists();
}

private function teamModificationError($user, $academicYear)
{
    $project1 = ProjectCourse::where('order', 1)->first();

    if (!$project1) {
        return response()->json([
            'success' => false,
            'message' => 'Capstone Project I is not configured.'
        ], 422);
    }

    $project1Enrollment = $user->enrollments()
        ->where('academic_year_id', $academicYear->id)
        ->where('project_course_id', $project1->id)
        ->where('status', 'in_progress')
        ->first();

    if (!$project1Enrollment) {
        return response()->json([
            'success' => false,
            'message' => 'Only students enrolled in Capstone Project I can modify team membership.'
        ], 403);
    }

    $rules = ProjectRule::first();

    if (!$rules || !$rules->project1_team_formation_deadline) {
        return response()->json([
            'success' => false,
            'message' => 'Project I team formation deadline is not configured.'
        ], 422);
    }

    if (now()->greaterThan($rules->project1_team_formation_deadline)) {
        return response()->json([
            'success' => false,
            'message' => 'Team formation deadline has passed. Team changes are no longer allowed.'
        ], 403);
    }

    return null;
}
    public function index(Request $request)
{
    try {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated.'
            ], 401);
        }

        $academicYear = AcademicYear::where('is_active', 1)->first();

        if (!$academicYear) {
            return response()->json([
                'success' => false,
                'message' => 'No active academic year found.'
            ], 404);
        }

        if (!$this->hasActiveCapstoneEnrollment($user, $academicYear)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not enrolled in an active capstone project.'
            ], 403);
        }

        $membership = TeamMembership::with([
                'team.academicYear',
                'team.department',
                'team.members.user',
                'team.supervisors'
            ])
            ->where('student_user_id', $user->id)
            ->where('academic_year_id', $academicYear->id)
            ->where('status', 'active')
            ->whereHas('team', function ($q) use ($academicYear) {
                $q->where('academic_year_id', $academicYear->id);
            })
            ->first();

        if (!$membership) {
            return response()->json([
                'success' => false,
                'message' => 'You are not in any team.'
            ], 404);
        }

        $team = $membership->team;

        $proposal = $team->proposals()
            ->whereIn('status', ['approved', 'completed'])
            ->latest()
            ->first();

        $projectRules = ProjectRule::getCurrent();

        return response()->json([
            'success' => true,
            'data' => [
                'team' => [
                    'id' => $team->id,
                    'academic_year' => $team->academicYear?->code,
                    'department' => $team->department?->name,
                    'leader_id' => $team->leader_user_id,
                    'is_leader' => (int) $team->leader_user_id === (int) $user->id,
                    'min_members' => $projectRules?->min_team_size,
                    'max_members' => $projectRules?->max_team_size,
                    'project1_team_formation_deadline' => $projectRules?->project1_team_formation_deadline,
                ],
                'project' => $proposal ? [
                    'id' => $proposal->id,
                    'title' => $proposal->title,
                    'description' => $proposal->description,
                    'status' => $proposal->status,
                    'technologies' => $proposal->technologies,
                ] : null,
                'members' => $team->members
                    ->where('status', 'active')
                    ->filter(fn ($member) => $member->user)
                    ->map(function ($member) {
                        return [
                            'id' => $member->student_user_id,
                            'name' => $member->user?->full_name,
                            'role' => $member->role_in_team,
                        ];
                    })->values(),
                'supervisors' => $team->supervisors
                    ->filter(fn ($supervisor) => $supervisor->is_active)
                    ->map(function ($supervisor) {
                        return [
                            'id' => $supervisor->id,
                            'name' => $supervisor->full_name,
                            'email' => $supervisor->email,
                            'phone' => $supervisor->phone,
                            'role' => $supervisor->pivot->supervisor_role,
                        ];
                    })->values(),
            ]
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Server error.',
            'error' => config('app.debug') ? $e->getMessage() : null,
        ], 500);
    }
}

public function leave(Request $request)
{
    $user = $request->user();

    DB::beginTransaction();

    try {
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated.'
            ], 401);
        }

        $academicYear = AcademicYear::where('is_active', 1)->first();

        if (!$academicYear) {
            return response()->json([
                'success' => false,
                'message' => 'No active academic year found.'
            ], 404);
        }

        if ($error = $this->teamModificationError($user, $academicYear)) {
            DB::rollBack();
            return $error;
        }

        $membership = TeamMembership::where('student_user_id', $user->id)
            ->where('academic_year_id', $academicYear->id)
            ->where('status', 'active')
            ->whereHas('team', function ($q) use ($academicYear) {
                $q->where('academic_year_id', $academicYear->id);
            })
            ->first();

        if (!$membership) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'You are not in any team.'
            ], 404);
        }

        $team = $membership->team;
        $isLeader = (int) $team->leader_user_id === (int) $user->id;
        $leavingMemberName = $user->full_name;

        $activeMembers = TeamMembership::where('team_id', $team->id)
            ->where('academic_year_id', $academicYear->id)
            ->where('status', 'active')
            ->with('user')
            ->get();

        $remainingMembers = $activeMembers
            ->where('student_user_id', '!=', $user->id)
            ->values();

        if ($remainingMembers->isEmpty()) {
            $membership->update([
                'status' => 'left',
                'left_at' => now(),
            ]);

            $team->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'You have left the team. The team was deleted because it has no remaining members.',
                'team_deleted' => true,
            ], 200);
        }

        if ($isLeader) {
            $newLeader = $remainingMembers->first();

            $team->update([
                'leader_user_id' => $newLeader->student_user_id,
            ]);

            TeamMembership::where('team_id', $team->id)
                ->where('student_user_id', $newLeader->student_user_id)
                ->where('academic_year_id', $academicYear->id)
                ->update([
                    'role_in_team' => 'leader',
                ]);

            TeamMembership::where('team_id', $team->id)
                ->where('student_user_id', $user->id)
                ->where('academic_year_id', $academicYear->id)
                ->update([
                    'role_in_team' => 'member',
                ]);

            foreach ($remainingMembers as $member) {
                if ($member->user) {
                    DatabaseNotification::create([
                        'id' => (string) \Illuminate\Support\Str::uuid(),
                        'type' => 'leadership_transferred',
                        'notifiable_type' => User::class,
                        'notifiable_id' => $member->user->id,
                        'academic_year_id' => $academicYear->id,
                        'data' => [
                            'type' => 'leadership_transferred',
                            'team_id' => $team->id,
                            'old_leader_id' => $user->id,
                            'old_leader_name' => $leavingMemberName,
                            'new_leader_id' => $newLeader->student_user_id,
                            'new_leader_name' => $newLeader->user?->full_name,
                            'message' => "Leadership has been transferred from {$leavingMemberName} to {$newLeader->user?->full_name}.",
                            'icon' => 'crown',
                            'color' => 'yellow',
                            'created_at' => now(),
                        ],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        foreach ($remainingMembers as $member) {
            if ($member->user) {
                DatabaseNotification::create([
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'type' => 'member_left',
                    'notifiable_type' => User::class,
                    'notifiable_id' => $member->user->id,
                    'academic_year_id' => $academicYear->id,
                    'data' => [
                        'type' => 'member_left',
                        'team_id' => $team->id,
                        'leaving_member_id' => $user->id,
                        'leaving_member_name' => $leavingMemberName,
                        'message' => "{$leavingMemberName} has left the team.",
                        'icon' => 'user-minus',
                        'color' => 'orange',
                        'created_at' => now(),
                    ],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $membership->update([
            'status' => 'left',
            'left_at' => now(),
        ]);
        \App\Services\ChatService::syncTeamChatParticipants($team);

        Request::where('academic_year_id', $academicYear->id)
            ->where('status', 'pending')
            ->where(function ($q) use ($user) {
                $q->where('from_user_id', $user->id)
                  ->orWhere('to_user_id', $user->id);
            })
            ->update([
                'status' => 'rejected',
            ]);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'You have left the team successfully.',
            'team_deleted' => false,
        ], 200);

    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'Failed to leave the team.',
            'error' => config('app.debug') ? $e->getMessage() : null,
        ], 500);
    }
}
/**
 * Leave a note to team
 */
public function leaveNote(Request $request)
{
    $user = $request->user();

    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'User not authenticated.'
        ], 401);
    }

    $request->validate([
        'note' => 'required|string|max:1000',
    ]);

    $activeAcademicYear = AcademicYear::where('is_active', true)->first();

    if (!$activeAcademicYear) {
        return response()->json([
            'success' => false,
            'message' => 'No active academic year found.'
        ], 400);
    }

    if (!$this->hasActiveCapstoneEnrollment($user, $activeAcademicYear)) {
        return response()->json([
            'success' => false,
            'message' => 'You are not enrolled in an active capstone project.'
        ], 403);
    }

    $membership = TeamMembership::where('student_user_id', $user->id)
        ->where('academic_year_id', $activeAcademicYear->id)
        ->where('status', 'active')
        ->first();

    if (!$membership) {
        return response()->json([
            'success' => false,
            'message' => 'You are not in any team.'
        ], 403);
    }

    $team = $membership->team;

    $teamNote = TeamNote::create([
        'team_id' => $team->id,
        'user_id' => $user->id,
        'note' => $request->note,
    ]);

    $members = TeamMembership::where('team_id', $team->id)
        ->where('academic_year_id', $activeAcademicYear->id)
        ->where('status', 'active')
        ->where('student_user_id', '!=', $user->id)
        ->with('user')
        ->get();

    foreach ($members as $member) {
        if ($member->user) {
            DatabaseNotification::create([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'type' => 'team_note',
                'notifiable_type' => User::class,
                'notifiable_id' => $member->user->id,
                'academic_year_id' => $activeAcademicYear->id,
                'data' => [
                    'type' => 'team_note',
                    'from_user_id' => $user->id,
                    'from_user_name' => $user->full_name,
                    'team_id' => $team->id,
                    'team_name' => $team->name ?? "Team {$team->id}",
                    'note' => $request->note,
                    'message' => "{$user->full_name} left a note.",
                    'icon' => 'message',
                    'color' => 'gray',
                    'created_at' => now(),
                ],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    return response()->json([
        'success' => true,
        'message' => 'Note sent successfully.',
        'data' => [
            'note_id' => $teamNote->id,
            'note' => $request->note,
            'created_at' => $teamNote->created_at,
        ]
    ], 201);
}
}