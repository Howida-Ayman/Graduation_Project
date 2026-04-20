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
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\DB;

class StudentsRequestsController extends Controller
{
    /**
     * الفرق المتاحة للانضمام
     */
    public function availableTeams()
    {
        $user = request()->user();

        $academicYear = AcademicYear::where('is_active', 1)->first();

        if (!$academicYear) {
            return response()->json([
                'success' => false,
                'message' => 'No active academic year found'
            ], 404);
        }

        if (!$user || !$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is not activated'
            ], 403);
        }

        $activeEnrollment = $user->enrollments()
            ->where('academic_year_id', $academicYear->id)
            ->where('status', 'active')
            ->first();

        if (!$activeEnrollment) {
            return response()->json([
                'success' => false,
                'message' => 'You are not allowed to access teams in this academic year'
            ], 403);
        }

        $maxTeamSize = ProjectRule::getMaxTeamSize();

        $teams = Team::with([
                'department',
                'leader',
                'members.user' => function ($q) {
                    $q->where('is_active', 1);
                }
            ])
            ->withCount([
                'members as members_count' => function ($q) {
                    $q->where('status', 'active');
                }
            ])
            ->where('academic_year_id', $academicYear->id)
            ->having('members_count', '<', $maxTeamSize)
            ->get(['id', 'leader_user_id', 'department_id']);

        $sentTeamRequests = Request::where('from_user_id', $user->id)
            ->where('academic_year_id', $academicYear->id)
            ->where('status', 'pending')
            ->whereNotNull('team_id')
            ->pluck('team_id')
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => $teams->map(function ($team) use ($sentTeamRequests, $maxTeamSize) {
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
            })
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
                'message' => 'No active academic year found'
            ], 404);
        }

        if (!$user || !$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is not activated'
            ], 403);
        }

        $activeEnrollment = $user->enrollments()
            ->where('academic_year_id', $academicYear->id)
            ->where('status', 'active')
            ->first();

        if (!$activeEnrollment) {
            return response()->json([
                'success' => false,
                'message' => 'You are not allowed to access students in this academic year'
            ], 403);
        }

        $sentRequests = Request::where('from_user_id', $user->id)
            ->where('academic_year_id', $academicYear->id)
            ->where('status', 'pending')
            ->pluck('to_user_id')
            ->toArray();

        $query = User::where('role_id', 4)
            ->where('is_active', 1)
            ->where('id', '!=', $user->id)
            ->whereHas('enrollments', function ($q) use ($academicYear) {
                $q->where('academic_year_id', $academicYear->id)
                  ->where('status', 'active');
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

        $students = $query->get(['id', 'full_name', 'track_name', 'profile_image_url']);

        return response()->json([
            'success' => true,
            'data' => $students->map(function ($student) use ($sentRequests) {
                return [
                    'id' => $student->id,
                    'name' => $student->full_name,
                    'track' => $student->track_name,
                    'profile_image' => $student->profile_image_url,
                    'can_request' => !in_array($student->id, $sentRequests),
                ];
            })
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
                'message' => 'No active academic year found'
            ], 404);
        }

        if (!$user || !$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is not activated'
            ], 403);
        }

        $activeEnrollment = $user->enrollments()
            ->where('academic_year_id', $academicYear->id)
            ->where('status', 'active')
            ->first();

        if (!$activeEnrollment) {
            return response()->json([
                'success' => false,
                'message' => 'You are not allowed to send requests in this academic year'
            ], 403);
        }

        $membership = TeamMembership::where('student_user_id', $user->id)
            ->where('academic_year_id', $academicYear->id)
            ->where('status', 'active')
            ->first();

        $hasTeam = !is_null($membership);
        $isLeader = $hasTeam && $membership->team && ((int) $membership->team->leader_user_id === (int) $user->id);

        $request->validate([
            'to_user_id' => 'required|exists:users,id',
            'team_id' => 'nullable|exists:teams,id',
            'request_type' => 'required|in:team_join,team_form,team_invite',
        ]);

        if ((int) $request->to_user_id === (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot send request to yourself'
            ], 400);
        }

        $targetUser = User::where('id', $request->to_user_id)
            ->where('role_id', 4)
            ->where('is_active', 1)
            ->whereHas('enrollments', function ($q) use ($academicYear) {
                $q->where('academic_year_id', $academicYear->id)
                  ->where('status', 'active');
            })
            ->first();

        if (!$targetUser) {
            return response()->json([
                'success' => false,
                'message' => 'Target user is not active in the current academic year'
            ], 400);
        }

        if ($request->request_type === 'team_invite') {
            if (!$isLeader) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only team leader can send invites'
                ], 403);
            }

            $teamId = $membership->team_id;

            $targetHasTeam = TeamMembership::where('student_user_id', $request->to_user_id)
                ->where('academic_year_id', $academicYear->id)
                ->where('status', 'active')
                ->exists();

            if ($targetHasTeam) {
                return response()->json([
                    'success' => false,
                    'message' => 'Target user is already in a team'
                ], 400);
            }

            $request->merge(['team_id' => $teamId]);
        }

        if ($request->request_type === 'team_join') {
            if ($hasTeam) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are already in a team'
                ], 400);
            }

            if (!$request->team_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Team ID is required for team_join request'
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
                    'message' => 'Team not found in active academic year'
                ], 404);
            }

            $maxTeamSize = ProjectRule::getMaxTeamSize();

            if ($team->members_count >= $maxTeamSize) {
                return response()->json([
                    'success' => false,
                    'message' => 'Team is full'
                ], 400);
            }
        }

        // team_form: المرسل ممكن يبقى خارج تيم أو ليدر في تيم قائم
        if ($request->request_type === 'team_form') {
            if ($hasTeam && !$isLeader) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only team leader can continue building the team'
                ], 403);
            }

            $targetHasTeam = TeamMembership::where('student_user_id', $request->to_user_id)
                ->where('academic_year_id', $academicYear->id)
                ->where('status', 'active')
                ->exists();

            if ($targetHasTeam) {
                return response()->json([
                    'success' => false,
                    'message' => 'Target user is already in a team'
                ], 400);
            }

            // لو المرسل بالفعل في تيم، لازم يبقى نفس تيمه هي اللي هتكبر
            if ($hasTeam) {
                $maxTeamSize = ProjectRule::getMaxTeamSize();

                $currentSize = TeamMembership::where('team_id', $membership->team_id)
                    ->where('academic_year_id', $academicYear->id)
                    ->where('status', 'active')
                    ->count();

                if ($currentSize >= $maxTeamSize) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Team is full'
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
                'message' => 'A similar pending request already exists'
            ], 400);
        }

        DB::beginTransaction();

        try {
            $newRequest = Request::create([
                'academic_year_id' => $academicYear->id,
                'from_user_id' => $user->id,
                'to_user_id' => $request->to_user_id,
                'team_id' => $request->request_type === 'team_join'
                    ? $request->team_id
                    : ($request->request_type === 'team_invite' ? $request->team_id : null),
                'request_type' => $request->request_type,
                'status' => 'pending',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Request sent successfully',
                'data' => [
                    'request_id' => $newRequest->id,
                    'status' => 'pending',
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to send request',
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
                'message' => 'No active academic year found'
            ], 404);
        }

        $query = Request::with(['fromUser', 'team'])
            ->where('to_user_id', $user->id)
            ->where('academic_year_id', $academicYear->id)
            ->where('status', 'pending');

        if ($request->type == 'teams') {
            $query->whereNotNull('team_id');
        } elseif ($request->type == 'students') {
            $query->whereNull('team_id');
        }

        $requests = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $requests->map(function ($req) {
                return [
                    'id' => $req->id,
                    'from_user' => [
                        'id' => $req->fromUser->id,
                        'name' => $req->fromUser->full_name,
                        'track' => $req->fromUser->track_name,
                        'profile_image' => $req->fromUser->profile_image_url,
                    ],
                    'request_type' => $req->request_type,
                    'team' => $req->team ? [
                        'id' => $req->team->id,
                        'name' => $req->team->name ?? 'Team',
                    ] : null,
                    'created_at' => $req->created_at,
                ];
            })
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
                'message' => 'No active academic year found'
            ], 404);
        }

        $requests = Request::with(['toUser', 'team'])
            ->where('from_user_id', $user->id)
            ->where('academic_year_id', $academicYear->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $requests->map(function ($req) {
                return [
                    'id' => $req->id,
                    'to_user' => [
                        'id' => $req->toUser->id,
                        'name' => $req->toUser->full_name,
                        'track' => $req->toUser->track_name,
                        'profile_image' => $req->toUser->profile_image_url,
                    ],
                    'request_type' => $req->request_type,
                    'team' => $req->team ? [
                        'id' => $req->team->id,
                        'name' => $req->team->name ?? 'Team',
                    ] : null,
                    'status' => $req->status,
                    'created_at' => $req->created_at,
                ];
            })
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
                'message' => 'No active academic year found'
            ], 404);
        }

        if (!$user || !$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is not activated'
            ], 403);
        }

        $activeEnrollment = $user->enrollments()
            ->where('academic_year_id', $academicYear->id)
            ->where('status', 'active')
            ->first();

        if (!$activeEnrollment) {
            return response()->json([
                'success' => false,
                'message' => 'You are not allowed to respond to requests in this academic year'
            ], 403);
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
                'message' => 'Request not found or already processed'
            ], 404);
        }

        DB::beginTransaction();

        try {
            $req->update(['status' => $request->status]);

            if ($request->status === 'accepted') {
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
                            'message' => 'Team not found in active academic year'
                        ], 404);
                    }

                    $alreadyMember = TeamMembership::where('team_id', $team->id)
                        ->where('student_user_id', $req->from_user_id)
                        ->where('academic_year_id', $academicYear->id)
                        ->where('status', 'active')
                        ->exists();

                    if ($alreadyMember) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'User is already a member of this team'
                        ], 400);
                    }

                    $fromHasAnotherTeam = TeamMembership::where('student_user_id', $req->from_user_id)
                        ->where('academic_year_id', $academicYear->id)
                        ->where('status', 'active')
                        ->where('team_id', '!=', $team->id)
                        ->exists();

                    if ($fromHasAnotherTeam) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'The requesting student already belongs to another team'
                        ], 400);
                    }

                    $maxTeamSize = ProjectRule::getMaxTeamSize();

                    if ($team->members_count >= $maxTeamSize) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'Team is already full'
                        ], 400);
                    }

                    TeamMembership::create([
                        'team_id' => $req->team_id,
                        'academic_year_id' => $team->academic_year_id,
                        'student_user_id' => $req->from_user_id,
                        'role_in_team' => 'member',
                        'status' => 'active',
                        'joined_at' => now(),
                    ]);

                    // أي طلبات pending تخص الطالب المنضم تتقفل
                    Request::where('academic_year_id', $academicYear->id)
                        ->where('status', 'pending')
                        ->where(function ($q) use ($req) {
                            $q->where('from_user_id', $req->from_user_id)
                              ->orWhere('to_user_id', $req->from_user_id);
                        })
                        ->where('id', '!=', $req->id)
                        ->update(['status' => 'rejected']);
                }

                elseif (in_array($req->request_type, ['team_form', 'team_invite'])) {
                    // الفكرة: from_user هو المحور
                    // لو عنده تيم بالفعل -> نضيف to_user لنفس التيم
                    // لو معندوش -> ننشئ تيم جديد

                    $fromMembership = TeamMembership::where('student_user_id', $req->from_user_id)
                        ->where('academic_year_id', $academicYear->id)
                        ->where('status', 'active')
                        ->first();

                    $toMembership = TeamMembership::where('student_user_id', $req->to_user_id)
                        ->where('academic_year_id', $academicYear->id)
                        ->where('status', 'active')
                        ->first();

                    if ($toMembership) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'The accepting student already belongs to a team'
                        ], 400);
                    }

                    $maxTeamSize = ProjectRule::getMaxTeamSize();

                    if ($fromMembership) {
                        $team = Team::where('id', $fromMembership->team_id)
                            ->where('academic_year_id', $academicYear->id)
                            ->first();

                        if (!$team) {
                            DB::rollBack();
                            return response()->json([
                                'success' => false,
                                'message' => 'Leader team not found in active academic year'
                            ], 404);
                        }

                        $currentSize = TeamMembership::where('team_id', $team->id)
                            ->where('academic_year_id', $academicYear->id)
                            ->where('status', 'active')
                            ->count();

                        if ($currentSize >= $maxTeamSize) {
                            DB::rollBack();
                            return response()->json([
                                'success' => false,
                                'message' => 'Team has reached maximum size'
                            ], 400);
                        }

                        $alreadyInSameTeam = TeamMembership::where('team_id', $team->id)
                            ->where('student_user_id', $req->to_user_id)
                            ->where('academic_year_id', $academicYear->id)
                            ->where('status', 'active')
                            ->exists();

                        if ($alreadyInSameTeam) {
                            DB::rollBack();
                            return response()->json([
                                'success' => false,
                                'message' => 'Student already belongs to this team'
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

                        if (is_null($req->team_id)) {
                            $req->update(['team_id' => $team->id]);
                        }
                    } else {
                        // أول قبول -> ننشئ تيم جديد
                        $fromUserDept = DB::table('student_profiles')
                            ->where('user_id', $req->from_user_id)
                            ->value('department_id');

                        $toUserDept = DB::table('student_profiles')
                            ->where('user_id', $req->to_user_id)
                            ->value('department_id');

                        $departmentId = ($fromUserDept == $toUserDept && $fromUserDept) ? $fromUserDept : 1;

                        $team = Team::create([
                            'academic_year_id' => $academicYear->id,
                            'department_id' => $departmentId,
                            'leader_user_id' => $req->from_user_id,
                        ]);

                        $req->update(['team_id' => $team->id]);

                        $milestones = Milestone::where('is_active', true)->get();

                        foreach ($milestones as $milestone) {
                            DB::table('team_milestone_status')->updateOrInsert(
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

                        TeamMembership::create([
                            'team_id' => $team->id,
                            'academic_year_id' => $academicYear->id,
                            'student_user_id' => $req->from_user_id,
                            'role_in_team' => 'leader',
                            'status' => 'active',
                            'joined_at' => now(),
                        ]);

                        TeamMembership::create([
                            'team_id' => $team->id,
                            'academic_year_id' => $academicYear->id,
                            'student_user_id' => $req->to_user_id,
                            'role_in_team' => 'member',
                            'status' => 'active',
                            'joined_at' => now(),
                        ]);
                    }

                    // أي طلبات pending تخص الطالب اللي قبل تتقفل
                    Request::where('academic_year_id', $academicYear->id)
                        ->where('status', 'pending')
                        ->where(function ($q) use ($req) {
                            $q->where('from_user_id', $req->to_user_id)
                              ->orWhere('to_user_id', $req->to_user_id);
                        })
                        ->where('id', '!=', $req->id)
                        ->update(['status' => 'rejected']);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Request ' . $request->status . ' successfully',
                'data' => [
                    'request_id' => $req->id,
                    'status' => $request->status,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to process request',
            ], 500);
        }
    }
}