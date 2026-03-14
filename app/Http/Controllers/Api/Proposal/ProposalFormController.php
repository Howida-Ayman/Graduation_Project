<?php

namespace App\Http\Controllers\Api\Proposal;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\ProjectType;
use App\Models\TeamMembership;
use Illuminate\Http\Request;

class ProposalFormController extends Controller
{
    public function getFormData(Request $request)
    {
        $user = $request->user();

        // 1. نجيب الفريق بتاع المستخدم
        $membership = TeamMembership::with(['team', 'team.members.user'])
            ->where('student_user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (!$membership) {
            return response()->json([
                'success' => false,
                'message' => 'You are not in any team'
            ], 403);
        }

        // 2. نجيب أعضاء الفريق
        $teamMembers = $membership->team->members
            ->where('status', 'active')
            ->map(function($member) {
                return [
                    'id' => $member->student_user_id,
                    'name' => $member->user?->full_name,
                ];
            })->values();

        // 3. نجيب الأقسام
        $departments = Department::where('is_active', true)
            ->get(['id', 'name']);

        // 4. نجيب أنواع المشاريع
        $projectTypes = ProjectType::where('is_active', true)
            ->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'data' => [
                'team_members' => $teamMembers,
                'departments' => $departments,
                'project_types' => $projectTypes,
            ]
        ]);
    }
}