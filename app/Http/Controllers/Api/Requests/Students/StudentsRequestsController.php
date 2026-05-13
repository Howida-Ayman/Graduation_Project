<?php

namespace App\Http\Controllers\Api\Requests\Students;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ProjectRule;
use App\Models\Request;
use App\Models\Team;
use App\Models\User;
use App\Models\TeamMembership;
use App\Models\Milestone;
use App\Models\ProjectCourse;
use App\Models\TeamMilestonStatus;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\ChatService;

class StudentsRequestsController extends Controller
{
    private function teamFormationAccessError($user, $academicYear)
    {
        if (!$user || !$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is not activated.'
            ], 403);
        }

        $project1 = ProjectCourse::where('order', 1)->first();

        if (!$project1) {
            return response()->json([
                'success' => false,
                'message' => 'Capstone Project I is not configured.'
            ], 422);
        }

        $enrollment = $user->enrollments()
            ->where('academic_year_id', $academicYear->id)
            ->where('project_course_id', $project1->id)
            ->where('status', 'in_progress')
            ->first();

        if (!$enrollment) {
            return response()->json([
                'success' => false,
                'message' => 'Only students enrolled in Capstone Project I can create or modify teams.'
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

    private function studentCanJoinProject1($studentId, $academicYear): bool
    {
        $project1 = ProjectCourse::where('order', 1)->first();

        if (!$project1) {
            return false;
        }

        return User::where('id', $studentId)
            ->where('role_id', 4)
            ->where('is_active', 1)
            ->whereHas('enrollments', function ($q) use ($academicYear, $project1) {
                $q->where('academic_year_id', $academicYear->id)
                    ->where('project_course_id', $project1->id)
                    ->where('status', 'in_progress');
            })
            ->exists();
    }

    /**
     * الفرق المتاحة للانضمام
     */
    public function availableTeams(HttpRequest $request)
    {
        $user = $request->user();

        $academicYear = AcademicYear::where('is_active', 1)->first();

        if (!$academicYear) {
            return response()->json([
                'success' => false,
                'message' => 'No active academic year found.'
            ], 404);
        }

        if ($error = $this->teamFormationAccessError($user, $academicYear)) {
            return $error;
        }

        $maxTeamSize = ProjectRule::getMaxTeamSize();
        $perPage = $request->per_page ?? 10;

        $teams = Team::with([
                'department',
                'leader',
                'members.user' => function ($q) {
                    $q->where('is_active', 1);
                }
            ])
            ->withCount([
                'members as members_count' => function ($q) use ($academicYear) {
                    $q->where('status', 'active')
                        ->where('academic_year_id', $academicYear->id);
                }
            ])
            ->where('academic_year_id', $academicYear->id)
            ->having('members_count', '<', $maxTeamSize)
            ->paginate($perPage, ['id', 'leader_user_id', 'department_id']);

        $sentTeamRequests = Request::where('from_user_id', $user->id)
            ->where('academic_year_id', $academicYear->id)
            ->where('status', 'pending')
            ->whereNotNull('team_id')
            ->pluck('team_id')
            ->toArray();

        $teams->getCollection()->transform(function ($team) use ($sentTeamRequests, $maxTeamSize) {
            return [
                'id' => $team->id,
                'leader_name' => $team->leader?->full_name,
                'leader_image' => $team->leader?->profile_image_url,
                'current_members' => $team->members_count,
                'max_members' => $maxTeamSize,
                'has_slot' => $team->members_count < $maxTeamSize,
                'can_request' => !in_array($team->id, $sentTeamRequests),
                'members' => $team->members
                    ->where('status', 'active')
                    ->filter(fn($member) => $member->user)
                    ->map(function ($member) {
                        return [
                            'id' => $member->student_user_id,
                            'name' => $member->user?->full_name,
                            'track' => $member->user?->track_name,
                            'profile_image' => $member->user?->profile_image_url,
                        ];
                    })->values(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $teams
        ]);
    }

    /**
     * الطلاب اللي مش في فرق
     */
    public function availableStudents(HttpRequest $request)
    {
        $user = $request->user();

        $academicYear = AcademicYear::where('is_active', 1)->first();

        if (!$academicYear) {
            return response()->json([
                'success' => false,
                'message' => 'No active academic year found.'
            ], 404);
        }

        if ($error = $this->teamFormationAccessError($user, $academicYear)) {
            return $error;
        }

        $project1 = ProjectCourse::where('order', 1)->first();

        $sentRequests = Request::where('from_user_id', $user->id)
            ->where('academic_year_id', $academicYear->id)
            ->where('status', 'pending')
            ->pluck('to_user_id')
            ->toArray();

        $perPage = $request->per_page ?? 10;

        $query = User::where('role_id', 4)
            ->where('is_active', 1)
            ->where('id', '!=', $user->id)
            ->whereHas('enrollments', function ($q) use ($academicYear, $project1) {
                $q->where('academic_year_id', $academicYear->id)
                    ->where('project_course_id', $project1->id)
                    ->where('status', 'in_progress');
            })
            ->whereDoesntHave('teamMemberships', function ($q) use ($academicYear) {
                $q->where('status', 'active')
                    ->where('academic_year_id', $academicYear->id);
            });

        if ($request->search) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', $search)
                    ->orWhere('track_name', 'like', $search);
            });
        }

        $students = $query->paginate($perPage, ['id', 'full_name', 'track_name', 'profile_image_url']);

        $students->getCollection()->transform(function ($student) use ($sentRequests) {
            return [
                'id' => $student->id,
                'name' => $student->full_name,
                'track' => $student->track_name,
                'profile_image' => $student->profile_image_url,
                'can_request' => !in_array($student->id, $sentRequests),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $students
        ]);
    }

    /**
     * إرسال طلب
     */
    public function sendRequest(HttpRequest $request)
    {
        $user = $request->user();

        $academicYear = AcademicYear::where('is_active', 1)->first();

        if (!$academicYear) {
            return response()->json([
                'success' => false,
                'message' => 'No active academic year found.'
            ], 404);
        }

        if ($error = $this->teamFormationAccessError($user, $academicYear)) {
            return $error;
        }

        $request->validate([
            'to_user_id' => 'required|exists:users,id',
            'team_id' => 'nullable|exists:teams,id',
            'request_type' => 'required|in:team_join,team_form,team_invite',
        ]);

        if ((int) $request->to_user_id === (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot send request to yourself.'
            ], 400);
        }

        if (!$this->studentCanJoinProject1($request->to_user_id, $academicYear)) {
            return response()->json([
                'success' => false,
                'message' => 'Target student must be active and enrolled in Capstone Project I.'
            ], 400);
        }

        $membership = TeamMembership::where('student_user_id', $user->id)
            ->where('academic_year_id', $academicYear->id)
            ->where('status', 'active')
            ->first();

        $hasTeam = !is_null($membership);

        $isLeader = $hasTeam
            && $membership->team
            && ((int) $membership->team->leader_user_id === (int) $user->id);

        if ($hasTeam && !$isLeader && $request->request_type !== 'team_join') {
            return response()->json([
                'success' => false,
                'message' => 'Only the team leader can send requests after the team is formed.'
            ], 403);
        }

        if ($request->request_type === 'team_invite') {
            if (!$isLeader) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only the team leader can send invites.'
                ], 403);
            }

            $targetHasTeam = TeamMembership::where('student_user_id', $request->to_user_id)
                ->where('academic_year_id', $academicYear->id)
                ->where('status', 'active')
                ->exists();

            if ($targetHasTeam) {
                return response()->json([
                    'success' => false,
                    'message' => 'Target student is already in a team.'
                ], 400);
            }

            $maxTeamSize = ProjectRule::getMaxTeamSize();

            $currentSize = TeamMembership::where('team_id', $membership->team_id)
                ->where('academic_year_id', $academicYear->id)
                ->where('status', 'active')
                ->count();

            if ($currentSize >= $maxTeamSize) {
                return response()->json([
                    'success' => false,
                    'message' => 'Team is full.'
                ], 400);
            }

            $request->merge(['team_id' => $membership->team_id]);
        }

        if ($request->request_type === 'team_join') {
            if ($hasTeam) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are already in a team.'
                ], 400);
            }

            if (!$request->team_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Team ID is required for join requests.'
                ], 400);
            }

            $team = Team::withCount([
                    'members as members_count' => function ($q) use ($academicYear) {
                        $q->where('status', 'active')
                            ->where('academic_year_id', $academicYear->id);
                    }
                ])
                ->where('id', $request->team_id)
                ->where('academic_year_id', $academicYear->id)
                ->first();

            if (!$team) {
                return response()->json([
                    'success' => false,
                    'message' => 'Team not found in the active academic year.'
                ], 404);
            }

            $maxTeamSize = ProjectRule::getMaxTeamSize();

            if ($team->members_count >= $maxTeamSize) {
                return response()->json([
                    'success' => false,
                    'message' => 'Team is full.'
                ], 400);
            }
        }

        if ($request->request_type === 'team_form') {
            if ($hasTeam && !$isLeader) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only the team leader can continue building the team.'
                ], 403);
            }

            $targetHasTeam = TeamMembership::where('student_user_id', $request->to_user_id)
                ->where('academic_year_id', $academicYear->id)
                ->where('status', 'active')
                ->exists();

            if ($targetHasTeam) {
                return response()->json([
                    'success' => false,
                    'message' => 'Target student is already in a team.'
                ], 400);
            }

            if ($hasTeam) {
                $maxTeamSize = ProjectRule::getMaxTeamSize();

                $currentSize = TeamMembership::where('team_id', $membership->team_id)
                    ->where('academic_year_id', $academicYear->id)
                    ->where('status', 'active')
                    ->count();

                if ($currentSize >= $maxTeamSize) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Team is full.'
                    ], 400);
                }
            }
        }

        $duplicatePending = Request::where('academic_year_id', $academicYear->id)
            ->where('from_user_id', $user->id)
            ->where('to_user_id', $request->to_user_id)
            ->where('request_type', $request->request_type)
            ->where('status', 'pending')
            ->when($request->team_id, function ($q) use ($request) {
                $q->where('team_id', $request->team_id);
            })
            ->exists();

        if ($duplicatePending) {
            return response()->json([
                'success' => false,
                'message' => 'A similar pending request already exists.'
            ], 400);
        }

        DB::beginTransaction();

        try {
            $newRequest = Request::create([
                'academic_year_id' => $academicYear->id,
                'from_user_id' => $user->id,
                'to_user_id' => $request->to_user_id,
                'team_id' => $request->team_id,
                'request_type' => $request->request_type,
                'status' => 'pending',
            ]);

            \App\Models\DatabaseNotification::create([
                'id' => (string) Str::uuid(),
                'type' => 'new_request',
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id' => $request->to_user_id,
                'academic_year_id' => $academicYear->id,
                'data' => [
                    'type' => 'new_request',
                    'request_id' => $newRequest->id,
                    'request_type' => $request->request_type,
                    'from_user_id' => $user->id,
                    'from_user_name' => $user->full_name,
                    'team_id' => $request->team_id,
                    'message' => "{$user->full_name} sent you a request",
                    'icon' => 'bell',
                    'color' => 'blue',
                    'created_at' => now(),
                ],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Request sent successfully.',
                'data' => [
                    'request_id' => $newRequest->id,
                    'status' => 'pending',
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to send request.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * جلب الطلبات المستلمة
     */
    public function receivedRequests(HttpRequest $request)
    {
        $user = $request->user();

        $academicYear = AcademicYear::where('is_active', 1)->first();

        if (!$academicYear) {
            return response()->json([
                'success' => false,
                'message' => 'No active academic year found.'
            ], 404);
        }

        $perPage = $request->per_page ?? 10;

        $requests = Request::with(['fromUser', 'team.leader'])
            ->where('academic_year_id', $academicYear->id)
            ->where('to_user_id', $user->id)
            ->where('status', 'pending')
            ->latest()
            ->paginate($perPage);

        $requests->getCollection()->transform(function ($req) {
            return [
                'id' => $req->id,
                'request_type' => $req->request_type,
                'status' => $req->status,
                'from_user' => [
                    'id' => $req->fromUser?->id,
                    'name' => $req->fromUser?->full_name,
                    'track' => $req->fromUser?->track_name,
                    'profile_image' => $req->fromUser?->profile_image_url,
                ],
                'team' => $req->team ? [
                    'id' => $req->team->id,
                    'leader_name' => $req->team->leader?->full_name,
                ] : null,
                'created_at' => $req->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $requests
        ]);
    }

    /**
     * جلب الطلبات المرسلة
     */
    public function sentRequests(HttpRequest $request)
    {
        $user = $request->user();

        $academicYear = AcademicYear::where('is_active', 1)->first();

        if (!$academicYear) {
            return response()->json([
                'success' => false,
                'message' => 'No active academic year found.'
            ], 404);
        }

        $perPage = $request->per_page ?? 10;

        $requests = Request::with(['toUser', 'team.leader'])
            ->where('academic_year_id', $academicYear->id)
            ->where('from_user_id', $user->id)
            ->latest()
            ->paginate($perPage);

        $requests->getCollection()->transform(function ($req) {
            return [
                'id' => $req->id,
                'request_type' => $req->request_type,
                'status' => $req->status,
                'to_user' => [
                    'id' => $req->toUser?->id,
                    'name' => $req->toUser?->full_name,
                    'track' => $req->toUser?->track_name,
                    'profile_image' => $req->toUser?->profile_image_url,
                ],
                'team' => $req->team ? [
                    'id' => $req->team->id,
                    'leader_name' => $req->team->leader?->full_name,
                ] : null,
                'created_at' => $req->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $requests
        ]);
    }

    /**
     * الرد على طلب (قبول/رفض)
     */
    public function respondRequest(HttpRequest $request, $id)
    {
        $user = $request->user();

        $academicYear = AcademicYear::where('is_active', 1)->first();

        if (!$academicYear) {
            return response()->json([
                'success' => false,
                'message' => 'No active academic year found.'
            ], 404);
        }

        if ($error = $this->teamFormationAccessError($user, $academicYear)) {
            return $error;
        }

        $request->validate([
            'status' => 'required|in:accepted,rejected',
        ]);

        $req = Request::with(['fromUser', 'toUser', 'team'])
            ->where('id', $id)
            ->where('academic_year_id', $academicYear->id)
            ->where('to_user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if (!$req) {
            return response()->json([
                'success' => false,
                'message' => 'Request not found or already processed.'
            ], 404);
        }

        DB::beginTransaction();

        try {
            $req->update(['status' => $request->status]);

            $statusText = $request->status === 'accepted' ? 'accepted' : 'rejected';
            $icon = $request->status === 'accepted' ? 'check-circle' : 'x-circle';
            $color = $request->status === 'accepted' ? 'green' : 'red';

            \App\Models\DatabaseNotification::create([
                'id' => (string) Str::uuid(),
                'type' => "request_{$statusText}",
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id' => $req->from_user_id,
                'academic_year_id' => $academicYear->id,
                'data' => [
                    'type' => "request_{$statusText}",
                    'request_id' => $req->id,
                    'request_type' => $req->request_type,
                    'team_id' => $req->team_id,
                    'message' => "Your request has been {$statusText} by {$user->full_name}",
                    'icon' => $icon,
                    'color' => $color,
                    'created_at' => now(),
                ],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($request->status === 'accepted') {

                if (!$this->studentCanJoinProject1($req->from_user_id, $academicYear)) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Requester is not eligible to join or form a team.'
                    ], 403);
                }

                if (!$this->studentCanJoinProject1($req->to_user_id, $academicYear)) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Receiver is not eligible to join or form a team.'
                    ], 403);
                }

                if ($req->request_type === 'team_join') {
                    $team = Team::withCount([
                            'members as members_count' => function ($q) use ($academicYear) {
                                $q->where('status', 'active')
                                    ->where('academic_year_id', $academicYear->id);
                            }
                        ])
                        ->where('id', $req->team_id)
                        ->where('academic_year_id', $academicYear->id)
                        ->first();

                    if (!$team) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'Team not found in the active academic year.'
                        ], 404);
                    }

                    if ((int) $team->leader_user_id !== (int) $user->id) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'Only the team leader can accept join requests.'
                        ], 403);
                    }

                    $maxTeamSize = ProjectRule::getMaxTeamSize();

                    if ($team->members_count >= $maxTeamSize) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'Team is full.'
                        ], 400);
                    }

                    $alreadyMember = TeamMembership::where('student_user_id', $req->from_user_id)
                        ->where('academic_year_id', $academicYear->id)
                        ->where('status', 'active')
                        ->exists();

                    if ($alreadyMember) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'Requester is already in a team.'
                        ], 400);
                    }

                    TeamMembership::create([
                        'team_id' => $team->id,
                        'academic_year_id' => $academicYear->id,
                        'student_user_id' => $req->from_user_id,
                        'role_in_team' => 'member',
                        'status' => 'active',
                        'joined_at' => now(),
                    ]);

                    ChatService::syncTeamChatParticipants($team);
                }

                if ($req->request_type === 'team_invite') {
                    $team = Team::withCount([
                            'members as members_count' => function ($q) use ($academicYear) {
                                $q->where('status', 'active')
                                    ->where('academic_year_id', $academicYear->id);
                            }
                        ])
                        ->where('id', $req->team_id)
                        ->where('academic_year_id', $academicYear->id)
                        ->where('leader_user_id', $req->from_user_id)
                        ->first();

                    if (!$team) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'Team not found or requester is not the team leader.'
                        ], 404);
                    }

                    $maxTeamSize = ProjectRule::getMaxTeamSize();

                    if ($team->members_count >= $maxTeamSize) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'Team is full.'
                        ], 400);
                    }

                    $alreadyMember = TeamMembership::where('student_user_id', $req->to_user_id)
                        ->where('academic_year_id', $academicYear->id)
                        ->where('status', 'active')
                        ->exists();

                    if ($alreadyMember) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'Receiver is already in a team.'
                        ], 400);
                    }

                    TeamMembership::create([
                        'team_id' => $team->id,
                        'academic_year_id' => $academicYear->id,
                        'student_user_id' => $req->to_user_id,
                        'role_in_team' => 'member',
                        'status' => 'active',
                        'joined_at' => now(),
                    ]);

                    ChatService::syncTeamChatParticipants($team);
                }

                if ($req->request_type === 'team_form') {

                    $fromMembership = TeamMembership::with('team')
                        ->where('student_user_id', $req->from_user_id)
                        ->where('academic_year_id', $academicYear->id)
                        ->where('status', 'active')
                        ->first();

                    $toHasTeam = TeamMembership::where('student_user_id', $req->to_user_id)
                        ->where('academic_year_id', $academicYear->id)
                        ->where('status', 'active')
                        ->exists();

                    if ($toHasTeam) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'Target student is already in a team.'
                        ], 400);
                    }

                    if ($fromMembership) {
                        // فريق موجود - إضافة عضو جديد
                        $team = $fromMembership->team;

                        if (!$team || (int) $team->leader_user_id !== (int) $req->from_user_id) {
                            DB::rollBack();
                            return response()->json([
                                'success' => false,
                                'message' => 'Only the team leader can add members to the team.'
                            ], 403);
                        }

                        $maxTeamSize = ProjectRule::getMaxTeamSize();

                        $currentSize = TeamMembership::where('team_id', $team->id)
                            ->where('academic_year_id', $academicYear->id)
                            ->where('status', 'active')
                            ->count();

                        if ($currentSize >= $maxTeamSize) {
                            DB::rollBack();
                            return response()->json([
                                'success' => false,
                                'message' => 'Team is full.'
                            ], 400);
                        }

                        TeamMembership::create([
                            'team_id' => $team->id,
                            'academic_year_id' => $academicYear->id,
                            'student_user_id' => $req->to_user_id,
                            'role_in_team' => 'member',
                            'status' => 'active',
                            'joined_at' => now(),
                        ]);

                        ChatService::syncTeamChatParticipants($team);
                        
                    } else {
                        // \Log::info('Creating NEW team');
                        
                        // جلب الـ leader
                        $leaderUser = User::find($req->from_user_id);
                        $departmentId = $leaderUser->studentprofile?->department_id ?? 1;
                        
                        // 1. إنشاء فريق جديد
                        $team = Team::create([
                            'academic_year_id' => $academicYear->id,
                            'department_id' => $departmentId,
                            'leader_user_id' => $req->from_user_id,
                        ]);
                        
                        // \Log::info('Team created with ID: ' . $team->id);
                        
                        // 2. إضافة الـ leader كعضو
                        $leaderMembership = TeamMembership::create([
                            'team_id' => $team->id,
                            'academic_year_id' => $academicYear->id,
                            'student_user_id' => $req->from_user_id,
                            'role_in_team' => 'leader',
                            'status' => 'active',
                            'joined_at' => now(),
                        ]);
                        
                        // \Log::info('Leader membership created: ' . ($leaderMembership ? 'yes' : 'no'));
                        
                        // 3. إضافة العضو الجديد
                        $memberMembership = TeamMembership::create([
                            'team_id' => $team->id,
                            'academic_year_id' => $academicYear->id,
                            'student_user_id' => $req->to_user_id,
                            'role_in_team' => 'member',
                            'status' => 'active',
                            'joined_at' => now(),
                        ]);
                        
                        // \Log::info('Member membership created: ' . ($memberMembership ? 'yes' : 'no'));
                        
                        // 4. إنشاء milestones (قبل الشات عشان نتأكد)
                        $milestones = Milestone::where('is_active', true)->get();
                        foreach ($milestones as $milestone) {
                            TeamMilestonStatus::updateOrCreate(
                                [
                                    'team_id' => $team->id,
                                    'milestone_id' => $milestone->id,
                                ],
                                [
                                    'status' => 'pending_submission',
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]
                            );
                        }
                        // \Log::info('Milestones created');
                        
                        // 5. إنشاء الشات (في الآخر)
                        ChatService::createTeamChat($team);
                        // \Log::info('Chat created for team: ' . $team->id);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Request ' . $request->status . ' successfully.',
                'data' => [
                    'request_id' => $req->id,
                    'status' => $request->status,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to process request.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}