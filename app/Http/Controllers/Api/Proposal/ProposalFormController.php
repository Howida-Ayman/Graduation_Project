<?php


namespace App\Http\Controllers\Api\Proposal;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\ProjectCourse;
use App\Models\ProjectType;
use App\Models\TeamMembership;
use Illuminate\Http\Request;

class ProposalFormController extends Controller
{
    public function getFormData(Request $request)
    {
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

        $project1 = ProjectCourse::where('order', 1)->first();

        if (!$project1) {
            return response()->json([
                'success' => false,
                'message' => 'Capstone Project I is not configured.'
            ], 422);
        }

        $activeEnrollment = $user->enrollments()
            ->where('academic_year_id', $academicYear->id)
            ->where('project_course_id', $project1->id)
            ->where('status', 'in_progress')
            ->first();

        if (!$activeEnrollment) {
            return response()->json([
                'success' => false,
                'message' => 'Only students enrolled in Capstone Project I can access the proposal form.'
            ], 403);
        }

        $membership = TeamMembership::with([
                'team',
                'team.members.user' => function ($q) {
                    $q->where('is_active', 1);
                }
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
            ], 403);
        }

        $team = $membership->team;

        if (!$team) {
            return response()->json([
                'success' => false,
                'message' => 'Team not found.'
            ], 404);
        }

        if ((int) $team->leader_user_id !== (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Only the team leader can access the proposal form.'
            ], 403);
        }

        $teamMembers = $team->members
            ->where('status', 'active')
            ->filter(fn ($member) => $member->user)
            ->map(function ($member) {
                return [
                    'id' => $member->student_user_id,
                    'name' => $member->user?->full_name,
                    'role_in_team' => $member->role_in_team,
                ];
            })->values();

        $departments = Department::where('is_active', true)
            ->get(['id', 'name']);

        $projectTypes = ProjectType::where('is_active', true)
            ->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'data' => [
                'academic_year' => [
                    'id' => $academicYear->id,
                    'code' => $academicYear->code,
                ],
                'team' => [
                    'id' => $team->id,
                    'leader_user_id' => $team->leader_user_id,
                ],
                'team_members' => $teamMembers,
                'departments' => $departments,
                'project_types' => $projectTypes,
            ]
        ], 200);
    }
}
