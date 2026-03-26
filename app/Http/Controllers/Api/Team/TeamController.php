<?php

namespace App\Http\Controllers\Api\Team;

use App\Http\Controllers\Controller;
use App\Models\ProjectRule;
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
            $projectRules = ProjectRule::getCurrent();

            return response()->json([
                'success' => true,
                'data' => [
                    'team' => [
                        'id' => $team->id,
                        'academic_year' => $team->academicYear?->code,
                        'department' => $team->department?->name,
                        'leader_id' => $team->leader_user_id,
                        'min_members' => $projectRules?->min_team_size,
                        'max_members' => $projectRules?->max_team_size,
                        'team_formation_deadline' => $projectRules?->team_formation_deadline,
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
        
        $hasApprovedProposal = Proposal::where('team_id', $team->id)
            ->where('status', 'approved')
            ->exists();
        
        if ($hasApprovedProposal) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot leave the team after the proposal has been approved. Contact the admin.'
            ], 403);
        }
        
        $activeMembers = TeamMembership::where('team_id', $team->id)
            ->where('status', 'active')
            ->get();
        
        $activeMembersCount = $activeMembers->count();
        $minTeamSize = ProjectRule::getMinTeamSize();
        
        // لو العدد هيقل عن الحد الأدنى، يتلغي الفريق
        if ($activeMembersCount - 1 < $minTeamSize) {
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
        
        if ($isLeader) {
            $newLeader = $activeMembers->where('student_user_id', '!=', $user->id)->first();
            
            if ($newLeader) {
                $team->leader_user_id = $newLeader->student_user_id;
                $team->save();
                
                TeamMembership::where('team_id', $team->id)
                    ->where('student_user_id', $user->id)
                    ->update(['role_in_team' => 'member']);
                
                TeamMembership::where('team_id', $team->id)
                    ->where('student_user_id', $newLeader->student_user_id)
                    ->update(['role_in_team' => 'leader']);
            }
        }
        
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
}