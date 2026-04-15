<?php

namespace App\Http\Controllers\Api\Proposal;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Department;
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
                'message' => 'User not authenticated'
            ], 401);
        }

        // 1) السنة الأكاديمية الفعالة
        $academicYear = AcademicYear::where('is_active', 1)->first();

        if (!$academicYear) {
            return response()->json([
                'success' => false,
                'message' => 'No active academic year found'
            ], 404);
        }

        // 2) التأكد إن الطالب active في السنة الحالية
        $activeEnrollment = $user->enrollments()
            ->where('academic_year_id', $academicYear->id)
            ->where('status', 'active')
            ->first();

        if (!$activeEnrollment) {
            return response()->json([
                'success' => false,
                'message' => 'You are not allowed to access this form in the current academic year'
            ], 403);
        }

        // 3) نجيب الفريق بتاع المستخدم في السنة الحالية فقط
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
                'message' => 'You are not in any team'
            ], 403);
        }

        // 4) أعضاء الفريق
        $teamMembers = $membership->team->members
            ->where('status', 'active')
            ->filter(fn($member) => $member->user)
            ->map(function ($member) {
                return [
                    'id' => $member->student_user_id,
                    'name' => $member->user?->full_name,
                ];
            })->values();

        // 5) الأقسام
        $departments = Department::where('is_active', true)
            ->get(['id', 'name']);

        // 6) أنواع المشاريع
        $projectTypes = ProjectType::where('is_active', true)
            ->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'data' => [
                'academic_year' => [
                    'id' => $academicYear->id,
                    'code' => $academicYear->code,
                ],
                'team_members' => $teamMembers,
                'departments' => $departments,
                'project_types' => $projectTypes,
            ]
        ], 200);
    }
}