<?php

namespace App\Http\Controllers\Api\Requests\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\Proposal;
use App\Models\Request;
use App\Models\Team;
use App\Models\User;
use App\Models\TeamMembership;
use App\Models\TeamSupervisor;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\DB;
use App\Notifications\SupervisionRequestNotification;

class SupervisionRequestsController extends Controller
{
public function availableSupervisors(HttpRequest $request)
    {
        $user = $request->user();
        
        $membership = TeamMembership::where('student_user_id', $user->id)
            ->where('status', 'active')
            ->first();
        
        if (!$membership) {
            return response()->json([
                'success' => false,
                'message' => 'You are not in any team'
            ], 403);
        }
        
        $team = $membership->team;
        
        if ($team->leader_user_id != $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Only the team leader can view available supervisors'
            ], 403);
        }

            $hasApprovedProposal = Proposal::where('team_id', $team->id)
        ->where('status', 'approved')
        ->exists();
    
    if (!$hasApprovedProposal) {
        return response()->json([
            'success' => false,
            'message' => 'Your team must have an approved proposal first before requesting supervisors'
        ], 403);
    }
    
        
        $query = User::whereIn('role_id', [2, 3])
            ->where('is_active', true);
        
        if ($request->type == 'doctor') {
            $query->where('role_id', 2);
        } elseif ($request->type == 'ta') {
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
        
        $supervisors = $query->get(['id', 'full_name', 'email', 'role_id', 'track_name', 'profile_image_url']);
        
        return response()->json([
            'success' => true,
            'data' => [
                'doctors' => $supervisors->where('role_id', 2)->values()->map(function($supervisor) {
                    return [
                        'id' => $supervisor->id,
                        'name' => $supervisor->full_name,
                        'email' => $supervisor->email,
                        'role' => 'doctor',
                        'track' => $supervisor->track_name,
                        'profile_image' => $supervisor->profile_image_url,
                    ];
                }),
                'tas' => $supervisors->where('role_id', 3)->values()->map(function($supervisor) {
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
        
        $membership = TeamMembership::where('student_user_id', $user->id)
            ->where('status', 'active')
            ->first();
        
        if (!$membership) {
            return response()->json([
                'success' => false,
                'message' => 'You are not in any team'
            ], 403);
        }
        
        $team = $membership->team;
        
        if ($team->leader_user_id != $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Only the team leader can send supervision requests'
            ], 403);
        }
         
        $hasApprovedProposal = Proposal::where('team_id', $team->id)
                ->where('status', 'approved')
                ->exists();
            
            if (!$hasApprovedProposal) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your team must have an approved proposal first before requesting supervisors'
                ], 403);
            }
    
        
        $request->validate([
            'supervisor_id' => 'required|exists:users,id',
            'role' => 'required|in:doctor,ta',
        ]);
        
        $supervisor = User::find($request->supervisor_id);
        
        if (!in_array($supervisor->role_id, [2, 3])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid supervisor'
            ], 400);
        }
        
        $expectedRole = $request->role;
        if (($expectedRole == 'doctor' && $supervisor->role_id != 2) ||
            ($expectedRole == 'ta' && $supervisor->role_id != 3)) {
            return response()->json([
                'success' => false,
                'message' => "The selected user is not a {$expectedRole}"
            ], 400);
        }
        
        $existingSupervisor = TeamSupervisor::where('team_id', $team->id)
            ->whereNull('ended_at')
            ->where('supervisor_role', $request->role)
            ->exists();
        
        if ($existingSupervisor) {
            return response()->json([
                'success' => false,
                'message' => "Your team already has a {$request->role} supervisor"
            ], 400);
        }
        
        $existing = TeamSupervisor::where('team_id', $team->id)
            ->where('supervisor_user_id', $supervisor->id)
            ->whereNull('ended_at')
            ->exists();
        
        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'This supervisor is already assigned to your team'
            ], 400);
        }
        
        DB::beginTransaction();
        
        try {
            $supervisionRequest = Request::create([
                'from_user_id' => $user->id,
                'to_user_id' => $request->supervisor_id,
                'team_id' => $team->id,
                'request_type' => 'supervision',
                'status' => 'pending',
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Supervision request sent successfully',
                'data' => [
                    'request_id' => $supervisionRequest->id,
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
    

}