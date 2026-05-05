<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Models\AcademicYear;
use App\Models\TeamMembership;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
{
    $user = User::where('national_id', $request->national_id)->first();

    if (!$user) {
        return response()->json([
            'message' => 'Invalid credentials.',
        ], 401);
    }

    if (!$user->is_active) {
        return response()->json([
            'message' => 'Your account is not activated.',
        ], 403);
    }

    if (!Auth::attempt([
        'national_id' => $request->national_id,
        'password' => $request->password
    ])) {
        return response()->json([
            'message' => 'Invalid credentials.',
        ], 401);
    }

    $user = Auth::user();
    $token = $user->createToken('api_login')->plainTextToken;

    $activeAcademicYear = AcademicYear::where('is_active', true)->first();

    $project1Enrollment = null;
    $project2Enrollment = null;
    $currentEnrollment = null;
    $currentProjectCourse = null;

    $membership = null;
    $hasTeam = false;
    $isLeader = false;
    $teamId = null;

    if (strtolower($user->role?->code ?? '') === 'student' && $activeAcademicYear) {
        $enrollments = $user->enrollments()
            ->with('projectCourse')
            ->where('academic_year_id', $activeAcademicYear->id)
            ->get();

        $project1Enrollment = $enrollments->first(
            fn ($e) => (int) $e->projectCourse?->order === 1
        );

        $project2Enrollment = $enrollments->first(
            fn ($e) => (int) $e->projectCourse?->order === 2
        );

        $currentEnrollment = $enrollments
            ->where('status', 'in_progress')
            ->sortByDesc(fn ($e) => (int) $e->projectCourse?->order)
            ->first();

        $currentProjectCourse = $currentEnrollment?->projectCourse;

        $membership = TeamMembership::with('team')
            ->where('student_user_id', $user->id)
            ->where('academic_year_id', $activeAcademicYear->id)
            ->where('status', 'active')
            ->first();

        $hasTeam = !is_null($membership);
        $teamId = $membership?->team_id;

        if ($hasTeam && $membership->team) {
            $isLeader = ((int) $membership->team->leader_user_id === (int) $user->id);
        }
    }

    $data = [
        'id' => $user->id,
        'role_id' => $user->role_id,
        'role_code' => $user->role?->code,
        'national_id' => $user->national_id,
        'full_name' => $user->full_name,
        'email' => $user->email,
        'track_name' => $user->track_name,
        'profile_image_url' => $user->profile_image_url,
        'phone' => $user->phone,
        'is_active' => $user->is_active,

        'active_academic_year' => $activeAcademicYear ? [
            'id' => $activeAcademicYear->id,
            'code' => $activeAcademicYear->code,
        ] : null,

        'current_project_course' => $currentProjectCourse ? [
            'id' => $currentProjectCourse->id,
            'name' => $currentProjectCourse->name,
            'order' => $currentProjectCourse->order,
        ] : null,

        'project1_status' => $project1Enrollment?->status,
        'project2_status' => $project2Enrollment?->status,

        'has_team' => $hasTeam,
        'is_leader' => $isLeader,
        'team_id' => $teamId,
    ];

    return response()->json([
        'message' => 'Login successful.',
        'user' => $data,
        'token' => $token
    ], 200);
}

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ], 200);
    }
}