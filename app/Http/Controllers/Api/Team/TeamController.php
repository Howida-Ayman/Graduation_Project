<?php

namespace App\Http\Controllers\Api\Team;

use App\Http\Controllers\Controller;
use App\Models\Proposal;
use App\Models\TeamMembership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeamController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $membership = TeamMembership::with([
                'team.academicYear',
                'team.department',
                'team.members.user',
                'team.supervisors'
            ])
            ->where('student_user_id', $user->id)
            ->where('status', 'active')
            ->first();

            if (!$membership) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not in any team'
                ], 404);
            }

            $team = $membership->team;
            
            $proposal = $team->proposals()
                ->whereIn('status', ['approved', 'completed'])
                ->latest()
                ->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'team' => [
                        'id' => $team->id,
                        'academic_year' => $team->academicYear?->code,
                        'department' => $team->department?->name,
                        'leader_id' => $team->leader_user_id,
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
                        ->map(function($member) {
                            return [
                                'id' => $member->student_user_id,
                                'name' => $member->user?->full_name,
                                'role' => $member->role_in_team,
                            ];
                        })->values(),
                    'supervisors' => $team->supervisors
                        ->map(function($supervisor) {
                            return [
                                'id' => $supervisor->id,
                                'name' => $supervisor->full_name,
                                'email' => $supervisor->email,
                                'phone' => $supervisor->phone,
                                'role' => $supervisor->pivot->supervisor_role,
                            ];
                        })->values(),
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server error',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

public function leave(Request $request)
{
    $user = $request->user();
    
    DB::beginTransaction();
    
    try {
        $membership = TeamMembership::where('student_user_id', $user->id)
            ->where('status', 'active')
            ->first();
        
        if (!$membership) {
            return response()->json([
                'success' => false,
                'message' => 'You are not in any team'
            ], 404);
        }
        
        $team = $membership->team;
        $isLeader = ($team->leader_user_id == $user->id);
        
        if ($isLeader) {
            $otherMembers = TeamMembership::where('team_id', $team->id)
                ->where('student_user_id', '!=', $user->id)
                ->where('status', 'active')
                ->count();

            
            if ($otherMembers > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot leave the team while there are other members. Transfer leadership first or disband the team.'
                ], 400);
            }
            
            // ✅ leader لوحده: نحذف كل حاجة
            DB::table('previous_projects')->where('team_id', $team->id)->delete();
            Proposal::where('team_id', $team->id)->update(['status' => 'cancelled']);
            TeamMembership::where('team_id', $team->id)->delete();
            DB::table('team_supervisors')->where('team_id', $team->id)->delete();
            $team->delete();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Team disbanded successfully',
                'team_disbanded' => true
            ]);
        }
        
        // member عادي: بس نخليه left
        $membership->status = 'left';
        $membership->left_at = now();
        $membership->save();
        
        DB::commit();
        
        return response()->json([
            'success' => true,
            'message' => 'You have left the team successfully',
            'team_disbanded' => false
        ]);
        
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Error leaving team',
            'error' => $e->getMessage()
        ], 500);
    }
}



    public function transferLeadership(Request $request)
{
    $user = $request->user();
    
    $request->validate([
        'new_leader_id' => 'required|exists:users,id',
    ]);
    
    DB::beginTransaction();
    
    try {
        // 1. جلب عضوية الـ leader الحالي
        $membership = TeamMembership::where('student_user_id', $user->id)
            ->where('status', 'active')
            ->first();
        
        if (!$membership) {
            return response()->json([
                'success' => false,
                'message' => 'You are not in any team'
            ], 404);
        }
        
        $team = $membership->team;
        
        // 2. التأكد إن المستخدم هو الـ leader
        if ($team->leader_user_id != $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Only the team leader can transfer leadership'
            ], 403);
        }
        
        // 3. التأكد إن العضو الجديد في نفس الفريق ونشط
        $newLeaderMembership = TeamMembership::where('team_id', $team->id)
            ->where('student_user_id', $request->new_leader_id)
            ->where('status', 'active')
            ->first();
        
        if (!$newLeaderMembership) {
            return response()->json([
                'success' => false,
                'message' => 'New leader must be an active member of the team'
            ], 400);
        }
        
        // 4. تحديث leader في جدول teams
        $team->leader_user_id = $request->new_leader_id;
        $team->save();
        
        // 5. تحديث roles في team_memberships
        TeamMembership::where('team_id', $team->id)
            ->where('student_user_id', $user->id)
            ->update(['role_in_team' => 'member']);
        
        TeamMembership::where('team_id', $team->id)
            ->where('student_user_id', $request->new_leader_id)
            ->update(['role_in_team' => 'leader']);
        
        DB::commit();
        
        return response()->json([
            'success' => true,
            'message' => 'Leadership transferred successfully',
            'data' => [
                'new_leader_id' => $request->new_leader_id,
                'new_leader_name' => $newLeaderMembership->user?->full_name,
            ]
        ]);
        
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Failed to transfer leadership',
            'error' => $e->getMessage()
        ], 500);
    }
}
}