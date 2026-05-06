<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\DefenseCommitteeResource;
use App\Models\AcademicYear;
use App\Models\DefenseCommittee;
use App\Models\GraduationProject;
use App\Models\ProjectCourse;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DefenseCommitteeController extends Controller
{
    public function projects(Request $request)
    {
        $request->validate([
            'project_course_id' => 'required|exists:project_courses,id',
        ]);

        $academicYear = AcademicYear::where('is_active', 1)->first();

        if (!$academicYear) {
            return response()->json([
                'message' => 'No active academic year found.'
            ], 404);
        }

        $projectCourse = ProjectCourse::findOrFail($request->project_course_id);

        $projects = GraduationProject::with([
                'team.department',
                'proposal',
            ])
            ->where('academic_year_id', $academicYear->id)
            ->whereHas('team', function ($q) use ($academicYear, $projectCourse) {
                $q->where('academic_year_id', $academicYear->id)
                    ->whereHas('members.user.enrollments', function ($q) use ($academicYear, $projectCourse) {
                        $q->where('academic_year_id', $academicYear->id)
                            ->where('project_course_id', $projectCourse->id)
                            ->where('status', 'in_progress');
                    });
            })
            ->whereHas('proposal', function ($q) {
                $q->where('status', 'approved');
            })
            ->whereDoesntHave('team.defenseCommittees', function ($q) use ($projectCourse) {
                $q->where('project_course_id', $projectCourse->id);
            })
            ->get()
            ->map(function ($project) use ($projectCourse) {
                return [
                    'team_id' => $project->team_id,
                    'project_course' => [
                        'id' => $projectCourse->id,
                        'name' => $projectCourse->name,
                        'order' => $projectCourse->order,
                    ],
                    'project_title' => $project->proposal?->title,
                    'project_category' => $project->proposal?->category,
                    'department' => [
                        'id' => $project->team?->department?->id,
                        'name' => $project->team?->department?->name,
                    ],
                ];
            })
            ->values();

        return response()->json([
            'message' => 'Projects retrieved successfully.',
            'data' => $projects,
        ], 200);
    }

    public function committeeOptions(Request $request)
    {
        $request->validate([
            'team_id' => 'required|exists:teams,id',
        ]);

        $academicYear = AcademicYear::where('is_active', 1)->first();

        if (!$academicYear) {
            return response()->json([
                'message' => 'No active academic year found.'
            ], 404);
        }

        $team = Team::with('currentSupervisors')
            ->where('id', $request->team_id)
            ->where('academic_year_id', $academicYear->id)
            ->first();

        if (!$team) {
            return response()->json([
                'message' => 'Team not found in active academic year.'
            ], 404);
        }

        $excludedIds = $team->currentSupervisors->pluck('id')->toArray();

        $doctors = User::where('role_id', 2)
            ->where('is_active', 1)
            ->whereNotIn('id', $excludedIds)
            ->get(['id', 'full_name', 'email']);

        $tas = User::where('role_id', 3)
            ->where('is_active', 1)
            ->whereNotIn('id', $excludedIds)
            ->get(['id', 'full_name', 'email']);

        return response()->json([
            'message' => 'Committee options retrieved successfully.',
            'data' => [
                'doctors' => $doctors,
                'tas' => $tas,
            ],
        ], 200);
    }

    public function store(Request $request)
{
    $request->validate([
        'team_id' => 'required|exists:teams,id',
        'project_course_id' => 'required|exists:project_courses,id',
        'scheduled_at' => 'required|date',
        'location' => 'required|string|max:255',

        'doctor_ids' => 'required|array|min:3',
        'doctor_ids.*' => 'required|distinct|exists:users,id',

        'ta_ids' => 'required|array|min:3',
        'ta_ids.*' => 'required|distinct|exists:users,id',
    ]);

    $admin = $request->user();

    DB::beginTransaction();

    try {
        $academicYear = AcademicYear::where('is_active', 1)->first();

        if (!$academicYear) {
            return response()->json([
                'message' => 'No active academic year found.'
            ], 404);
        }

        $projectCourse = ProjectCourse::findOrFail($request->project_course_id);

        $team = Team::with(['currentSupervisors'])
            ->where('id', $request->team_id)
            ->where('academic_year_id', $academicYear->id)
            ->first();

        if (!$team) {
            return response()->json([
                'message' => 'Team not found in active academic year.'
            ], 404);
        }

        $hasCourseEnrollment = $team->members()
            ->where('status', 'active')
            ->whereHas('user.enrollments', function ($q) use ($academicYear, $projectCourse) {
                $q->where('academic_year_id', $academicYear->id)
                    ->where('project_course_id', $projectCourse->id)
                    ->where('status', 'in_progress');
            })
            ->exists();

        if (!$hasCourseEnrollment) {
            return response()->json([
                'message' => 'This team is not currently enrolled in the selected project course.'
            ], 422);
        }

        $project = GraduationProject::where('team_id', $team->id)
            ->where('academic_year_id', $academicYear->id)
            ->first();

        if (!$project || !$project->proposal || $project->proposal->status !== 'approved') {
            return response()->json([
                'message' => 'Only approved projects can have a defense committee.'
            ], 422);
        }

        $alreadyHasCommittee = DefenseCommittee::where('team_id', $team->id)
            ->where('project_course_id', $projectCourse->id)
            ->exists();

        if ($alreadyHasCommittee) {
            return response()->json([
                'message' => 'This team already has a defense committee for this project course.'
            ], 409);
        }

        $allSelectedIds = array_merge($request->doctor_ids, $request->ta_ids);

        if (count($allSelectedIds) !== count(array_unique($allSelectedIds))) {
            return response()->json([
                'message' => 'The same user cannot be selected more than once in the defense committee.'
            ], 422);
        }

        $excludedIds = $team->currentSupervisors->pluck('id')->toArray();

        foreach ($allSelectedIds as $memberId) {
            if (in_array($memberId, $excludedIds)) {
                return response()->json([
                    'message' => 'Team supervisors cannot be assigned to the defense committee.'
                ], 422);
            }
        }

        $validDoctorsCount = User::whereIn('id', $request->doctor_ids)
            ->where('role_id', 2)
            ->where('is_active', 1)
            ->count();

if ($validDoctorsCount < 3) {
    return response()->json([
        'message' => 'At least 3 valid active doctors are required.'
    ], 422);
}

        $validTasCount = User::whereIn('id', $request->ta_ids)
            ->where('role_id', 3)
            ->where('is_active', 1)
            ->count();

        if ($validTasCount < 3) {
            return response()->json([
                'message' => 'At least 3 valid active teaching assistants are required.'
            ], 422);
        }

        $committee = DefenseCommittee::create([
            'academic_year_id' => $academicYear->id,
            'project_course_id' => $projectCourse->id,
            'team_id' => $team->id,
            'scheduled_at' => $request->scheduled_at,
            'location' => $request->location,
            'created_by_admin_id' => $admin->id,
            'status' => 'scheduled',
        ]);

        foreach ($request->doctor_ids as $index => $doctorId) {
            $committee->members()->create([
                'member_user_id' => $doctorId,
                'member_role' => 'doctor',
                'seat_order' => $index + 1,
            ]);
        }

        foreach ($request->ta_ids as $index => $taId) {
            $committee->members()->create([
                'member_user_id' => $taId,
                'member_role' => 'ta',
                'seat_order' => $index + 1,
            ]);
        }

        DB::commit();

        return response()->json([
            'message' => 'Defense committee created successfully.',
            'data' => new DefenseCommitteeResource($committee->fresh()->load([
                'projectCourse',
                'team.graduationProject.proposal',
                'members.member',
                'grade',
            ])),
        ], 201);

    } catch (\Throwable $th) {
        DB::rollBack();

        return response()->json([
            'message' => 'Something went wrong while creating the defense committee.',
            'error' => config('app.debug') ? $th->getMessage() : null,
        ], 500);
    }
}

    public function index(Request $request)
{
    $request->validate([
        'project_course_id' => 'required|exists:project_courses,id',
    ]);

    $academicYear = AcademicYear::where('is_active', 1)->first();

    if (!$academicYear) {
        return response()->json([
            'message' => 'No active academic year found.'
        ], 404);
    }

    $committees = DefenseCommittee::with([
            'projectCourse',
            'team.graduationProject.proposal',
            'members.member',
            'grade',
        ])
        ->where('academic_year_id', $academicYear->id)
        ->where('project_course_id', $request->project_course_id) // 👈 أهم سطر
        ->orderBy('scheduled_at', 'desc')
        ->get()
        ->map(fn ($committee) => new DefenseCommitteeResource($committee))
        ->values();

    return response()->json([
        'message' => 'Defense committees retrieved successfully.',
        'data' => $committees,
    ], 200);
}

    public function update(Request $request, $id)
{
    $request->validate([
        'scheduled_at' => 'sometimes|date',
        'location' => 'sometimes|string|max:255',

        'doctor_ids' => 'sometimes|array|min:3',
        'doctor_ids.*' => 'required|distinct|exists:users,id',

        'ta_ids' => 'sometimes|array|min:3',
        'ta_ids.*' => 'required|distinct|exists:users,id',
    ]);

    DB::beginTransaction();

    try {
        $academicYear = AcademicYear::where('is_active', 1)->first();

        if (!$academicYear) {
            return response()->json([
                'message' => 'No active academic year found.'
            ], 404);
        }

        $committee = DefenseCommittee::with([
                'team.currentSupervisors',
                'members',
                'grade',
            ])
            ->where('id', $id)
            ->where('academic_year_id', $academicYear->id)
            ->first();

        if (!$committee) {
            return response()->json([
                'message' => 'Defense committee not found in active academic year.'
            ], 404);
        }

        if ($committee->grade) {
            return response()->json([
                'message' => 'This defense committee cannot be edited because the final grade has already been entered.'
            ], 409);
        }

        $team = $committee->team;

        if (!$team) {
            return response()->json([
                'message' => 'Team not found for this defense committee.'
            ], 404);
        }

        $committeeData = [];

        if ($request->has('scheduled_at')) {
            $committeeData['scheduled_at'] = $request->scheduled_at;
        }

        if ($request->has('location')) {
            $committeeData['location'] = $request->location;
        }

        if (!empty($committeeData)) {
            $committee->update($committeeData);
        }

        $currentDoctorIds = $committee->members
            ->where('member_role', 'doctor')
            ->pluck('member_user_id')
            ->toArray();

        $currentTaIds = $committee->members
            ->where('member_role', 'ta')
            ->pluck('member_user_id')
            ->toArray();

        $doctorIds = $request->has('doctor_ids')
            ? $request->doctor_ids
            : $currentDoctorIds;

        $taIds = $request->has('ta_ids')
            ? $request->ta_ids
            : $currentTaIds;

        $allSelectedIds = array_merge($doctorIds, $taIds);

        if (count($allSelectedIds) !== count(array_unique($allSelectedIds))) {
            return response()->json([
                'message' => 'The same user cannot be selected more than once in the defense committee.'
            ], 422);
        }

        $excludedIds = $team->currentSupervisors->pluck('id')->toArray();

        foreach ($allSelectedIds as $memberId) {
            if (in_array($memberId, $excludedIds)) {
                return response()->json([
                    'message' => 'Team supervisors cannot be assigned to the defense committee.'
                ], 422);
            }
        }

        $validDoctorsCount = User::whereIn('id', $doctorIds)
            ->where('role_id', 2)
            ->where('is_active', 1)
            ->count();

        if ($validDoctorsCount < 3) {
            return response()->json([
                'message' => 'At least 3 valid active doctors are required.'
            ], 422);
        }

        $validTasCount = User::whereIn('id', $taIds)
            ->where('role_id', 3)
            ->where('is_active', 1)
            ->count();

        if ($validTasCount < 3) {
            return response()->json([
                'message' => 'At least 3 valid active teaching assistants are required.'
            ], 422);
        }

        if ($request->has('doctor_ids')) {
            $committee->members()
                ->where('member_role', 'doctor')
                ->delete();

            foreach ($doctorIds as $index => $doctorId) {
                $committee->members()->create([
                    'member_user_id' => $doctorId,
                    'member_role' => 'doctor',
                    'seat_order' => $index + 1,
                ]);
            }
        }

        if ($request->has('ta_ids')) {
            $committee->members()
                ->where('member_role', 'ta')
                ->delete();

            foreach ($taIds as $index => $taId) {
                $committee->members()->create([
                    'member_user_id' => $taId,
                    'member_role' => 'ta',
                    'seat_order' => $index + 1,
                ]);
            }
        }

        DB::commit();

        $updatedCommittee = DefenseCommittee::with([
            'projectCourse',
            'team.graduationProject.proposal',
            'members.member',
            'grade',
        ])->find($committee->id);

        return response()->json([
            'message' => 'Defense committee updated successfully.',
            'data' => new DefenseCommitteeResource($updatedCommittee),
        ], 200);

    } catch (\Throwable $th) {
        DB::rollBack();

        return response()->json([
            'message' => 'Something went wrong while updating the defense committee.',
            'error' => config('app.debug') ? $th->getMessage() : null,
        ], 500);
    }
}

    public function destroy($id)
    {
        $academicYear = AcademicYear::where('is_active', 1)->first();

        if (!$academicYear) {
            return response()->json([
                'message' => 'No active academic year found.'
            ], 404);
        }

        $committee = DefenseCommittee::with('grade')
            ->where('id', $id)
            ->where('academic_year_id', $academicYear->id)
            ->first();

        if (!$committee) {
            return response()->json([
                'message' => 'Defense committee not found in active academic year.'
            ], 404);
        }

        if ($committee->grade) {
            return response()->json([
                'message' => 'This defense committee cannot be deleted because the final grade has already been entered.'
            ], 409);
        }

        $committee->delete();

        return response()->json([
            'message' => 'Defense committee deleted successfully.'
        ], 200);
    }

  
}