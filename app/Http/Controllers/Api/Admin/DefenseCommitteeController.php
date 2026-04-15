<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\DefenseCommittee;
use App\Models\GraduationProject;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DefenseCommitteeController extends Controller
{
    public function projects()
    {
        $academicYear = AcademicYear::where('is_active', 1)->first();

        if (!$academicYear) {
            return response()->json([
                'message' => 'No active academic year found'
            ], 404);
        }

        $projects = GraduationProject::with([
                'team.department',
                'proposal',
            ])
            ->where('academic_year_id', $academicYear->id)
            ->whereHas('team', function ($q) use ($academicYear) {
                $q->where('academic_year_id', $academicYear->id);
            })
            ->whereDoesntHave('team.defenseCommittee')
            ->get()
            ->map(function ($project) {
                return [
                    'team_id' => $project->team_id,
                    'project_title' => $project->proposal?->title,
                    'category' => $project->proposal?->category,
                    'department' => [
                        'id' => $project->team?->department?->id,
                        'name' => $project->team?->department?->name,
                    ],
                ];
            })
            ->values();

        return response()->json([
            'message' => 'Projects retrieved successfully',
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
                'message' => 'No active academic year found'
            ], 404);
        }

        $team = Team::with('currentSupervisors')
            ->where('id', $request->team_id)
            ->where('academic_year_id', $academicYear->id)
            ->first();

        if (!$team) {
            return response()->json([
                'message' => 'Team not found in active academic year'
            ], 404);
        }

        $excludedIds = $team->currentSupervisors->pluck('id');

        $doctors = User::where('role_id', 2)
            ->where('is_active', 1)
            ->whereNotIn('id', $excludedIds)
            ->get(['id', 'full_name', 'email']);

        $tas = User::where('role_id', 3)
            ->where('is_active', 1)
            ->whereNotIn('id', $excludedIds)
            ->get(['id', 'full_name', 'email']);

        return response()->json([
            'message' => 'Committee options retrieved successfully',
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
            'scheduled_at' => 'required|date',
            'location' => 'required|string|max:255',
            'doctor_ids' => 'required|array|size:3',
            'doctor_ids.*' => 'required|distinct|exists:users,id',
            'ta_id' => 'required|exists:users,id',
        ]);

        $admin = $request->user();

        DB::beginTransaction();

        try {
            $academicYear = AcademicYear::where('is_active', 1)->first();

            if (!$academicYear) {
                return response()->json([
                    'message' => 'No active academic year found'
                ], 404);
            }

            $team = Team::with(['currentSupervisors', 'defenseCommittee'])
                ->where('id', $request->team_id)
                ->where('academic_year_id', $academicYear->id)
                ->first();

            if (!$team) {
                return response()->json([
                    'message' => 'Team not found in active academic year'
                ], 404);
            }

            $project = GraduationProject::where('team_id', $team->id)
                ->where('academic_year_id', $academicYear->id)
                ->first();

            if (!$project) {
                return response()->json([
                    'message' => 'This team does not have a graduation project yet.'
                ], 422);
            }

            $proposal = $project->proposal;

            if (!$proposal || $proposal->status !== 'approved') {
                return response()->json([
                    'message' => 'Only approved projects can have a defense committee.'
                ], 422);
            }

            if ($team->defenseCommittee) {
                return response()->json([
                    'message' => 'This team already has a defense committee.'
                ], 409);
            }

            $excludedIds = $team->currentSupervisors->pluck('id')->toArray();

            foreach ($request->doctor_ids as $doctorId) {
                if (in_array($doctorId, $excludedIds)) {
                    return response()->json([
                        'message' => 'Supervisors cannot be assigned to the defense committee.'
                    ], 422);
                }
            }

            if (in_array($request->ta_id, $excludedIds)) {
                return response()->json([
                    'message' => 'Supervisors cannot be assigned to the defense committee.'
                ], 422);
            }

            $validDoctorsCount = User::whereIn('id', $request->doctor_ids)
                ->where('role_id', 2)
                ->where('is_active', 1)
                ->count();

            if ($validDoctorsCount !== 3) {
                return response()->json([
                    'message' => 'Exactly 3 valid active doctors are required.'
                ], 422);
            }

            $validTa = User::where('id', $request->ta_id)
                ->where('role_id', 3)
                ->where('is_active', 1)
                ->exists();

            if (!$validTa) {
                return response()->json([
                    'message' => 'A valid active TA is required.'
                ], 422);
            }

            $committee = DefenseCommittee::create([
                'academic_year_id' => $project->academic_year_id,
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

            $committee->members()->create([
                'member_user_id' => $request->ta_id,
                'member_role' => 'ta',
                'seat_order' => null,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Defense committee created successfully.',
                'data' => $committee->load('members.member'),
            ], 201);

        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json([
                'message' => 'Something went wrong while creating the defense committee.',
            ], 500);
        }
    }

    public function index()
    {
        $academicYear = AcademicYear::where('is_active', 1)->first();

        if (!$academicYear) {
            return response()->json([
                'message' => 'No active academic year found'
            ], 404);
        }

        $committees = DefenseCommittee::with([
                'team.graduationProject.proposal',
                'members.member',
                'grade',
            ])
            ->where('academic_year_id', $academicYear->id)
            ->orderBy('scheduled_at', 'desc')
            ->get()
            ->map(function ($committee) {
                $doctors = $committee->members
                    ->where('member_role', 'doctor')
                    ->sortBy('seat_order')
                    ->values();

                $ta = $committee->members
                    ->firstWhere('member_role', 'ta');

                return [
                    'id' => $committee->id,
                    'team_id' => $committee->team_id,
                    'project_title' => $committee->team?->graduationProject?->proposal?->title,
                    'category' => $committee->team?->graduationProject?->proposal?->category,
                    'scheduled_at' => optional($committee->scheduled_at)->format('Y-m-d H:i:s'),
                    'date' => optional($committee->scheduled_at)->format('Y-m-d'),
                    'time' => optional($committee->scheduled_at)->format('H:i'),
                    'location' => $committee->location,
                    'status' => $committee->status,

                    'assistant' => $ta ? [
                        'id' => $ta->member?->id,
                        'name' => $ta->member?->full_name,
                    ] : null,

                    'doctor_1' => $doctors->get(0)?->member?->full_name,
                    'doctor_2' => $doctors->get(1)?->member?->full_name,
                    'doctor_3' => $doctors->get(2)?->member?->full_name,

                    'grade' => $committee->grade?->grade,
                ];
            })
            ->values();

        return response()->json([
            'message' => 'Defense committees retrieved successfully',
            'data' => $committees,
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'scheduled_at' => 'sometimes|date',
            'location' => 'sometimes|string|max:255',
            'doctor_ids' => 'sometimes|array|size:3',
            'doctor_ids.*' => 'required|distinct|exists:users,id',
            'ta_id' => 'sometimes|exists:users,id',
        ]);

        DB::beginTransaction();

        try {
            $academicYear = AcademicYear::where('is_active', 1)->first();

            if (!$academicYear) {
                return response()->json([
                    'message' => 'No active academic year found'
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
                    'message' => 'Defense committee not found in active academic year'
                ], 404);
            }

            // لو الدرجة دخلت خلاص ممنوع التعديل
            if ($committee->grade) {
                return response()->json([
                    'message' => 'This defense committee cannot be edited because the final grade has already been entered.'
                ], 409);
            }

            $team = $committee->team;
            $excludedIds = $team->currentSupervisors->pluck('id')->toArray();

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

            if ($request->has('doctor_ids')) {
                foreach ($request->doctor_ids as $doctorId) {
                    if (in_array($doctorId, $excludedIds)) {
                        return response()->json([
                            'message' => 'Supervisors cannot be assigned to the defense committee.'
                        ], 422);
                    }
                }

                $validDoctorsCount = User::whereIn('id', $request->doctor_ids)
                    ->where('role_id', 2)
                    ->where('is_active', 1)
                    ->count();

                if ($validDoctorsCount !== 3) {
                    return response()->json([
                        'message' => 'Exactly 3 valid active doctors are required.'
                    ], 422);
                }

                $committee->members()
                    ->where('member_role', 'doctor')
                    ->delete();

                foreach ($request->doctor_ids as $index => $doctorId) {
                    $committee->members()->create([
                        'member_user_id' => $doctorId,
                        'member_role' => 'doctor',
                        'seat_order' => $index + 1,
                    ]);
                }
            }

            if ($request->has('ta_id')) {
                if (in_array($request->ta_id, $excludedIds)) {
                    return response()->json([
                        'message' => 'Supervisors cannot be assigned to the defense committee.'
                    ], 422);
                }

                $validTa = User::where('id', $request->ta_id)
                    ->where('role_id', 3)
                    ->where('is_active', 1)
                    ->exists();

                if (!$validTa) {
                    return response()->json([
                        'message' => 'A valid active TA is required.'
                    ], 422);
                }

                $committee->members()
                    ->where('member_role', 'ta')
                    ->delete();

                $committee->members()->create([
                    'member_user_id' => $request->ta_id,
                    'member_role' => 'ta',
                    'seat_order' => null,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Defense committee updated successfully',
                'data' => $committee->fresh()->load([
                    'team.graduationProject.proposal',
                    'members.member',
                    'grade',
                ]),
            ], 200);

        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json([
                'message' => 'Something went wrong while updating the defense committee.',
            ], 500);
        }
    }

    public function destroy($id)
    {
        $academicYear = AcademicYear::where('is_active', 1)->first();

        if (!$academicYear) {
            return response()->json([
                'message' => 'No active academic year found'
            ], 404);
        }

        $committee = DefenseCommittee::with('grade')
            ->where('id', $id)
            ->where('academic_year_id', $academicYear->id)
            ->first();

        if (!$committee) {
            return response()->json([
                'message' => 'Defense committee not found in active academic year'
            ], 404);
        }

        if ($committee->grade) {
            return response()->json([
                'message' => 'This defense committee cannot be deleted because the final grade has already been entered.'
            ], 409);
        }

        $committee->delete();

        return response()->json([
            'message' => 'Defense committee deleted successfully'
        ], 200);
    }
}