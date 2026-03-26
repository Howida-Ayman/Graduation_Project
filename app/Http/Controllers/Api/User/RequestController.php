<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Request;
use App\Models\Team;
use App\Models\User;
use App\Models\TeamMembership;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\DB;

class RequestController extends Controller
{
    /**
     * الفرق المتاحة للانضمام
     */
    public function availableTeams()
    {
        $teams = Team::with(['department', 'leader', 'members.user'])
            ->withCount('members')
            ->having('members_count', '<', 6)
            ->get(['id', 'leader_user_id', 'department_id']);
        
        // جلب الطلبات المرسلة من المستخدم عشان نعرف مين بعت طلب قبل كده
        $user = request()->user();
        $sentTeamRequests = Request::where('from_user_id', $user->id)
            ->where('status', 'pending')
            ->whereNotNull('team_id')
            ->pluck('team_id')
            ->toArray();
        
        return response()->json([
            'success' => true,
            'data' => $teams->map(function($team) use ($sentTeamRequests) {
                return [
                    'id' => $team->id,
                    'leader_name' => $team->leader?->full_name,
                    'current_members' => $team->members_count,
                    'max_members' => 6,
                    'has_slot' => $team->members_count < 6,
                    'can_request' => !in_array($team->id, $sentTeamRequests),
                    'members' => $team->members->map(function($member) {
                        return [
                            'id' => $member->student_user_id,
                            'name' => $member->user?->full_name,
                            'track' => $member->user?->track_name,
                        ];
                    }),
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
        
        // جلب الطلبات المرسلة من المستخدم
        $sentRequests = Request::where('from_user_id', $user->id)
            ->where('status', 'pending')
            ->pluck('to_user_id')
            ->toArray();
        
        $query = User::where('role_id', 4)
            ->whereDoesntHave('teamMemberships', function($q) {
                $q->where('status', 'active');
            });
        
        // ✅ سيرش عام في الاسم والتخصص
        if ($request->search) {
            $search = '%' . $request->search . '%';
            $query->where(function($q) use ($search) {
                $q->where('full_name', 'like', $search)
                  ->orWhere('track_name', 'like', $search);
            });
        }
        
        $students = $query->get(['id', 'full_name', 'track_name']);
        
        return response()->json([
            'success' => true,
            'data' => $students->map(function($student) use ($sentRequests) {
                return [
                    'id' => $student->id,
                    'name' => $student->full_name,
                    'track' => $student->track_name,
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
        
        // التحقق من وجود فريق
        $membership = TeamMembership::where('student_user_id', $user->id)
            ->where('status', 'active')
            ->first();
        
        $hasTeam = !is_null($membership);
        $isLeader = $hasTeam && $membership->team->leader_user_id == $user->id;
        
        $request->validate([
            'to_user_id' => 'required|exists:users,id',
            'team_id' => 'nullable|exists:teams,id',
            'request_type' => 'required|in:team_join,team_form,team_invite',
        ]);
        
        // منع الإرسال لنفس المستخدم
        if ($request->to_user_id == $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot send request to yourself'
            ], 400);
        }
        
        // التحقق حسب نوع المستخدم
        if ($request->request_type == 'team_invite') {
            // بس الـ Leader يقدر يبعت دعوات
            if (!$isLeader) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only team leader can send invites'
                ], 403);
            }

            $team_id = $membership->team_id;
            
            // التأكد إن المستهدف مش في فريق
            $targetHasTeam = TeamMembership::where('student_user_id', $request->to_user_id)
                ->where('status', 'active')
                ->exists();
                
            if ($targetHasTeam) {
                return response()->json([
                    'success' => false,
                    'message' => 'Target user is already in a team'
                ], 400);
            }
            $request->merge(['team_id' => $team_id]);
        }
        
        if ($request->request_type == 'team_join') {
            // اللي مش في فريق بس يقدر يطلب انضمام
            if ($hasTeam) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are already in a team'
                ], 400);
            }
            
            // التأكد إن الفريق موجود ولديه مكان
            if (!$request->team_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Team ID is required for team_join request'
                ], 400);
            }
            
            $team = Team::withCount('members')->find($request->team_id);
            if ($team->members_count >= 6) {
                return response()->json([
                    'success' => false,
                    'message' => 'Team is full'
                ], 400);
            }
        }
        
        if ($request->request_type == 'team_form') {
            // اللي مش في فريق بس يقدر يطلب تكوين فريق
            if ($hasTeam) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are already in a team'
                ], 400);
            }
            
            // التأكد إن المستهدف مش في فريق
            $targetHasTeam = TeamMembership::where('student_user_id', $request->to_user_id)
                ->where('status', 'active')
                ->exists();
                
            if ($targetHasTeam) {
                return response()->json([
                    'success' => false,
                    'message' => 'Target user is already in a team'
                ], 400);
            }
        }
        
        DB::beginTransaction();
        
        try {
            $newRequest = Request::create([
                'from_user_id' => $user->id,
                'to_user_id' => $request->to_user_id,
                'team_id' => $request->request_type == 'team_join' ? $request->team_id : 
                ($request->request_type == 'team_invite' ? $request->team_id : null),
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
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * جلب الطلبات المستلمة
     */
    public function receivedRequests(HttpRequest $request)
    {
        $user = $request->user();
        
        $query = Request::with(['fromUser', 'team'])
            ->where('to_user_id', $user->id)
            ->where('status', 'pending');
        
        // ✅ فلتر حسب النوع
        if ($request->type == 'teams') {
            $query->whereNotNull('team_id');
        } elseif ($request->type == 'students') {
            $query->whereNull('team_id');
        }
        // type = 'all' أو مفيش -> كل الطلبات
        
        $requests = $query->orderBy('created_at', 'desc')->get();
        
        return response()->json([
            'success' => true,
            'data' => $requests->map(function($req) {
                return [
                    'id' => $req->id,
                    'from_user' => [
                        'id' => $req->fromUser->id,
                        'name' => $req->fromUser->full_name,
                        'track' => $req->fromUser->track_name,
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
        
        $requests = Request::with(['toUser', 'team'])
            ->where('from_user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $requests->map(function($req) {
                return [
                    'id' => $req->id,
                    'to_user' => [
                        'id' => $req->toUser->id,
                        'name' => $req->toUser->full_name,
                        'track' => $req->toUser->track_name,
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
        
        $request->validate([
            'status' => 'required|in:accepted,rejected',
        ]);
        
        $req = Request::with(['fromUser', 'toUser', 'team'])
            ->where('id', $id)
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
            // تحديث حالة الطلب
            $req->update(['status' => $request->status]);
            
            // لو الطلب مقبول
            if ($request->status == 'accepted') {
                if ($req->request_type == 'team_join') {
                    $team = Team::with('academicYear')->find($req->team_id);
                    
                    TeamMembership::create([
                        'team_id' => $req->team_id,
                        'academic_year_id' => $team->academic_year_id ?? 1,
                        'student_user_id' => $req->from_user_id,
                        'role_in_team' => 'member',
                        'status' => 'active',
                        'joined_at' => now(),
                    ]);
                } 
                elseif ($req->request_type == 'team_form') {
                    // إنشاء فريق جديد
                    $team = Team::create([
                        'academic_year_id' => 1,
                        'department_id' => 1,
                        'leader_user_id' => $req->from_user_id,
                    ]);
                    $req->update(['team_id' => $team->id]);
                    
                    // إضافة الـ leader
                    TeamMembership::create([
                        'team_id' => $team->id,
                        'academic_year_id' => $team->academic_year_id,
                        'student_user_id' => $req->from_user_id,
                        'role_in_team' => 'leader',
                        'status' => 'active',
                        'joined_at' => now(),
                    ]);
                    
                    // إضافة العضو التاني
                    TeamMembership::create([
                        'team_id' => $team->id,
                        'academic_year_id' => $team->academic_year_id,
                        'student_user_id' => $req->to_user_id,
                        'role_in_team' => 'member',
                        'status' => 'active',
                        'joined_at' => now(),
                    ]);
                }
                elseif ($req->request_type == 'team_invite') {
                    $team = Team::with('academicYear')->find($req->team_id);
                    
                    TeamMembership::create([
                        'team_id' => $team->id,
                        'academic_year_id' => $team->academic_year_id ?? 1,
                        'student_user_id' => $req->to_user_id,
                        'role_in_team' => 'member',
                        'status' => 'active',
                        'joined_at' => now(),
                    ]);
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
                'error' => $e->getMessage()
            ], 500);
        }
    }
}