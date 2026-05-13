<?php

namespace App\Http\Controllers\Api\Requests\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Proposal;
use App\Models\Request;
use App\Models\Team;
use App\Models\User;
use App\Models\TeamMembership;
use App\Models\TeamSupervisor;
use App\Models\ProjectCourse;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class SupervisionRequestsController extends Controller
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
    public function availableSupervisors(HttpRequest $request)
{
    $user = $request->user();

    $academicYear = AcademicYear::where('is_active', 1)->first();

    if (!$academicYear) {
        return response()->json([
            'success' => false,
            'message' => 'No active academic year found.'
        ], 404);
    }

    if (!$user || !$user->is_active) {
        return response()->json([
            'success' => false,
            'message' => 'Your account is not activated.'
        ], 403);
    }

    if (!$this->hasActiveCapstoneEnrollment($user, $academicYear)) {
        return response()->json([
            'success' => false,
            'message' => 'You are not enrolled in an active capstone project.'
        ], 403);
    }

    $membership = TeamMembership::where('student_user_id', $user->id)
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
        ], 403);
    }

    $team = $membership->team;

    if ((int) $team->leader_user_id !== (int) $user->id) {
        return response()->json([
            'success' => false,
            'message' => 'Only the team leader can view available supervisors.'
        ], 403);
    }

    $hasApprovedProposal = Proposal::where('team_id', $team->id)
        ->where('status', 'approved')
        ->exists();

    if (!$hasApprovedProposal) {
        return response()->json([
            'success' => false,
            'message' => 'Your team must have an approved proposal before requesting supervisors.'
        ], 403);
    }

    $query = User::whereIn('role_id', [2, 3])
        ->where('is_active', true);

    if ($request->type === 'doctor') {
        $query->where('role_id', 2);
    } elseif ($request->type === 'ta') {
        $query->where('role_id', 3);
    }

    if ($request->search) {
        $search = '%' . $request->search . '%';
        $query->where('full_name', 'like', $search);
    }

    $existingSupervisorIds = TeamSupervisor::where('team_id', $team->id)
        ->whereNull('ended_at')
        ->pluck('supervisor_user_id')
        ->toArray();

    $query->whereNotIn('id', $existingSupervisorIds);

    $supervisors = $query->get([
        'id',
        'full_name',
        'email',
        'role_id',
        'track_name',
        'profile_image_url'
    ]);

    return response()->json([
        'success' => true,
        'data' => [
            'doctors' => $supervisors->where('role_id', 2)->values()->map(function ($supervisor) {
                return [
                    'id' => $supervisor->id,
                    'name' => $supervisor->full_name,
                    'email' => $supervisor->email,
                    'role' => 'doctor',
                    'track' => $supervisor->track_name,
                    'profile_image' => $supervisor->profile_image_url,
                ];
            }),
            'tas' => $supervisors->where('role_id', 3)->values()->map(function ($supervisor) {
                return [
                    'id' => $supervisor->id,
                    'name' => $supervisor->full_name,
                    'email' => $supervisor->email,
                    'role' => 'ta',
                    'track' => $supervisor->track_name,
                    'profile_image' => $supervisor->profile_image_url,
                ];
            }),
        ]
    ]);
}

    public function requestSupervision(HttpRequest $request)
{
    $user = $request->user();

    $academicYear = AcademicYear::where('is_active', 1)->first();

    if (!$academicYear) {
        return response()->json([
            'success' => false,
            'message' => 'No active academic year found.'
        ], 404);
    }

    if (!$user || !$user->is_active) {
        return response()->json([
            'success' => false,
            'message' => 'Your account is not activated.'
        ], 403);
    }

    if (!$this->hasActiveCapstoneEnrollment($user, $academicYear)) {
        return response()->json([
            'success' => false,
            'message' => 'You are not allowed to request supervision because you are not enrolled in an active capstone project.'
        ], 403);
    }

    $membership = TeamMembership::where('student_user_id', $user->id)
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
        ], 403);
    }

    $team = $membership->team;

    if ((int) $team->leader_user_id !== (int) $user->id) {
        return response()->json([
            'success' => false,
            'message' => 'Only the team leader can send supervision requests.'
        ], 403);
    }

    $hasApprovedProposal = Proposal::where('team_id', $team->id)
        ->where('status', 'approved')
        ->exists();

    if (!$hasApprovedProposal) {
        return response()->json([
            'success' => false,
            'message' => 'Your team must have an approved proposal before requesting supervisors.'
        ], 403);
    }

    $request->validate([
        'supervisor_id' => 'required|exists:users,id',
        'role' => 'required|in:doctor,ta',
    ]);

    $supervisor = User::where('id', $request->supervisor_id)
        ->where('is_active', true)
        ->first();

    if (!$supervisor || !in_array($supervisor->role_id, [2, 3])) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid supervisor selected.'
        ], 400);
    }

    if (
        ($request->role === 'doctor' && (int) $supervisor->role_id !== 2) ||
        ($request->role === 'ta' && (int) $supervisor->role_id !== 3)
    ) {
        return response()->json([
            'success' => false,
            'message' => "The selected user is not a {$request->role}."
        ], 400);
    }

    $existingSupervisor = TeamSupervisor::where('team_id', $team->id)
        ->whereNull('ended_at')
        ->where('supervisor_role', $request->role)
        ->exists();

    if ($existingSupervisor) {
        return response()->json([
            'success' => false,
            'message' => "Your team already has a {$request->role} supervisor."
        ], 400);
    }

    $duplicatePending = Request::where('academic_year_id', $academicYear->id)
        ->where('team_id', $team->id)
        ->where('to_user_id', $request->supervisor_id)
        ->where('request_type', 'supervision')
        ->where('status', 'pending')
        ->exists();

    if ($duplicatePending) {
        return response()->json([
            'success' => false,
            'message' => 'A pending supervision request already exists for this supervisor.'
        ], 400);
    }

    DB::beginTransaction();

    try {
        $supervisionRequest = Request::create([
            'academic_year_id' => $academicYear->id,
            'from_user_id' => $user->id,
            'to_user_id' => $request->supervisor_id,
            'team_id' => $team->id,
            'request_type' => 'supervision',
            'status' => 'pending',
        ]);

        \App\Models\DatabaseNotification::create([
            'id' => (string) Str::uuid(),
            'type' => 'supervision_request',
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id' => $request->supervisor_id,
            'academic_year_id' => $academicYear->id,
            'data' => [
                'type' => 'supervision_request',
                'request_id' => $supervisionRequest->id,
                'team_id' => $team->id,
                'team_name' => $team->name ?? "Team {$team->id}",
                'message' => "Team {$team->id} has requested your supervision.",
                'icon' => 'user-plus',
                'color' => 'blue',
                'created_at' => now(),
            ],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Supervision request sent successfully.',
            'data' => [
                'request_id' => $supervisionRequest->id,
                'status' => 'pending',
            ]
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'Failed to send supervision request.',
            'error' => config('app.debug') ? $e->getMessage() : null,
        ], 500);
    }
}

    public function getRequests(HttpRequest $request)
    {
        $user = $request->user();

        $academicYear = AcademicYear::where('is_active', 1)->first();

        if (!$academicYear) {
            return response()->json([
                'message' => 'No active academic year found'
            ], 404);
        }

        $query = Request::where('to_user_id', $user->id)
            ->where('academic_year_id', $academicYear->id)
            ->where('request_type', 'supervision')
            ->with([
                'team.department',
                'team.members.user',
                'team.graduationProject.proposal',
            ]);

        if ($request->filled('status') && in_array($request->status, ['pending', 'accepted', 'rejected'])) {
            $query->where('status', $request->status);
        }

        if ($request->filled('department_id')) {
            $query->whereHas('team', function ($q) use ($request, $academicYear) {
                $q->where('academic_year_id', $academicYear->id)
                  ->where('department_id', $request->department_id);
            });
        }

        if ($request->filled('categories')) {
            $categories = $request->categories;

            if (!is_array($categories)) {
                $categories = [$categories];
            }

            $query->whereHas('team.graduationProject.proposal', function ($q) use ($categories) {
                $q->where(function ($subQ) use ($categories) {
                    foreach ($categories as $category) {
                        $subQ->orWhere('category', 'like', '%' . $category . '%');
                    }
                });
            });
        }

        $requests = $query->latest()->get();

        if ($request->filled('team_size')) {
            $teamSize = $request->team_size;

            $requests = $requests->filter(function ($item) use ($teamSize) {
                $count = $item->team?->members?->where('status', 'active')->count() ?? 0;

                return match ($teamSize) {
                    '1-3' => $count >= 1 && $count <= 3,
                    '4-5' => $count >= 4 && $count <= 5,
                    '5-6' => $count >= 5 && $count <= 6,
                    default => true,
                };
            })->values();
        }

        $formatted = $requests->map(function ($item) {
            $team = $item->team;
            $project = $team?->graduationProject;
            $proposal = $project?->proposal;

            return [
                'id' => $item->id,
                'status' => $item->status,
                'request_type' => $item->request_type,
                'requested_at' => optional($item->created_at)->format('M d, Y'),

                'team' => [
                    'id' => $team?->id,
                    'department_id' => $team?->department_id,
                    'department_name' => $team?->department?->name,
                    'members_count' => $team?->members?->where('status', 'active')->count() ?? 0,
                    'members' => $team?->members?->where('status', 'active')->map(function ($member) {
                        return [
                            'id' => $member->student_user_id,
                            'name' => $member->user?->full_name,
                            'role_in_team' => $member->role_in_team,
                        ];
                    })->values(),
                ],

                'project' => [
                    'title' => $proposal?->title,
                    'description' => $proposal?->description,
                    'problem_statement' => $proposal?->problem_statement,
                    'solution' => $proposal?->solution,
                    'image_url' => $project?->image_url ?? $proposal?->image_url,
                    'files' => $proposal?->attachment_file,
                    'technologies' => $proposal?->technologies,
                    'category' => $proposal?->category,
                ],
            ];
        });

        $baseSummary = Request::where('to_user_id', $user->id)
            ->where('academic_year_id', $academicYear->id)
            ->where('request_type', 'supervision');

        return response()->json([
            'message' => 'Requests retrieved successfully',
            'summary' => [
                'all' => (clone $baseSummary)->count(),
                'pending' => (clone $baseSummary)->where('status', 'pending')->count(),
                'accepted' => (clone $baseSummary)->where('status', 'accepted')->count(),
                'rejected' => (clone $baseSummary)->where('status', 'rejected')->count(),
            ],
            'filters' => [
                'categories' => $request->categories ?? [],
                'team_size' => $request->team_size,
            ],
            'data' => $formatted,
        ], 200);
    }

    public function respondToRequest(HttpRequest $request, $id)
{
    $user = $request->user();

    $academicYear = AcademicYear::where('is_active', 1)->first();

    if (!$academicYear) {
        return response()->json([
            'success' => false,
            'message' => 'No active academic year found.'
        ], 404);
    }

    if (!$user || !$user->is_active) {
        return response()->json([
            'success' => false,
            'message' => 'Your account is not activated.'
        ], 403);
    }

    $request->validate([
        'status' => 'required|in:accepted,rejected',
    ]);

    $teamRequest = Request::with(['team', 'fromUser', 'toUser'])
        ->where('id', $id)
        ->where('academic_year_id', $academicYear->id)
        ->where('to_user_id', $user->id)
        ->where('request_type', 'supervision')
        ->where('status', 'pending')
        ->first();

    if (!$teamRequest) {
        return response()->json([
            'success' => false,
            'message' => 'Supervision request not found or already processed.'
        ], 404);
    }

    $supervisorRole = ((int) $user->role_id === 2) ? 'doctor' : (((int) $user->role_id === 3) ? 'ta' : null);

    if (!$supervisorRole) {
        return response()->json([
            'success' => false,
            'message' => 'Only doctors and teaching assistants can respond to supervision requests.'
        ], 403);
    }

    DB::beginTransaction();

    try {
        $teamRequest->update([
            'status' => $request->status
        ]);

        if ($request->status === 'accepted') {
            $alreadyHasRole = TeamSupervisor::where('team_id', $teamRequest->team_id)
                ->where('supervisor_role', $supervisorRole)
                ->whereNull('ended_at')
                ->exists();

            if ($alreadyHasRole) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => "This team already has a {$supervisorRole} supervisor."
                ], 400);
            }

            TeamSupervisor::create([
                'team_id' => $teamRequest->team_id,
                'supervisor_user_id' => $user->id,
                'supervisor_role' => $supervisorRole,
                'assigned_at' => now(),
                'ended_at' => null,
            ]);
            \App\Services\ChatService::syncTeamChatParticipants($teamRequest->team);

            Request::where('academic_year_id', $academicYear->id)
                ->where('team_id', $teamRequest->team_id)
                ->where('request_type', 'supervision')
                ->where('status', 'pending')
                ->where('id', '!=', $teamRequest->id)
                ->whereHas('toUser', function ($q) use ($supervisorRole) {
                    if ($supervisorRole === 'doctor') {
                        $q->where('role_id', 2);
                    } else {
                        $q->where('role_id', 3);
                    }
                })
                ->update([
                    'status' => 'rejected'
                ]);
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Supervision request ' . $request->status . ' successfully.',
            'data' => [
                'request_id' => $teamRequest->id,
                'status' => $request->status,
            ]
        ]);

    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'Failed to process supervision request.',
            'error' => config('app.debug') ? $e->getMessage() : null,
        ], 500);
    }
}
}