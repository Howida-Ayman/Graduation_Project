<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AcademicYear;
use App\Models\Milestone;
use App\Models\MilestoneCommittee;
use App\Models\MilestoneCommitteeGrade;
use App\Models\MilestoneCommitteeMember;
use App\Models\Proposal;
use App\Models\Submission;
use App\Models\Team;
use App\Models\TeamSupervisor;
use App\Models\User;
use Illuminate\Support\Facades\DB;
class MilestoneCommitteeController extends Controller
{
    public function index()
{
    $committees = MilestoneCommittee::with([
            'team.members.user',
            'members.user',
        ])
        ->latest()
        ->get()
        ->map(function ($committee) {
            $proposal = Proposal::where('team_id', $committee->team_id)
                ->where('status', 'approved')
                ->latest()
                ->first();

            return [
                'id' => $committee->id,
                'team_id' => $committee->team_id,

                'project_title' => $proposal?->title,
                'project_category' => $proposal?->category,

                'doctors' => $committee->members
                    ->where('member_role', 'doctor')
                    ->map(fn ($member) => [
                        'id' => $member->user?->id,
                        'name' => $member->user?->full_name,
                        'email' => $member->user?->email,
                    ])
                    ->values(),

                'tas' => $committee->members
                    ->where('member_role', 'ta')
                    ->map(fn ($member) => [
                        'id' => $member->user?->id,
                        'name' => $member->user?->full_name,
                        'email' => $member->user?->email,
                    ])
                    ->values(),

                'created_at' => $committee->created_at,
            ];
        });

    return response()->json([
        'success' => true,
        'data' => $committees
    ], 200);
}
    public function eligibleTeams()
    {
        $academicYear = AcademicYear::where('is_active', 1)->first();

        if (!$academicYear) {
            return response()->json([
                'success' => false,
                'message' => 'No active academic year found.'
            ], 404);
        }

        $teams = Team::with(['members.user'])
            ->where('academic_year_id', $academicYear->id)
            ->whereDoesntHave('milestoneCommittee')
            ->whereHas('members.user.enrollments', function ($q) use ($academicYear) {
                $q->where('academic_year_id', $academicYear->id)
                    ->where('status', 'in_progress')
                    ->whereHas('projectCourse', function ($q) {
                        $q->where('order', 1);
                    });
            })
            ->get()
            ->map(function ($team) {
                $proposal = Proposal::where('team_id', $team->id)
                    ->where('status', 'approved')
                    ->latest()
                    ->first();

                return [
                    'team_id' => $team->id,
                    'members_count' => $team->members->where('status', 'active')->count(),
                    'project_title' => $proposal?->title,
                    'project_category' => $proposal?->category,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $teams
        ], 200);
    }

    public function formData($teamId)
    {
        $team = Team::with(['leader', 'members.user'])
            ->findOrFail($teamId);

        if ($team->milestoneCommittee) {
            return response()->json([
                'success' => false,
                'message' => 'This team already has a milestone committee.'
            ], 400);
        }

        $proposal = Proposal::where('team_id', $team->id)
            ->where('status', 'approved')
            ->latest()
            ->first();

        $excludedSupervisorIds = TeamSupervisor::where('team_id', $team->id)
            ->whereNull('ended_at')
            ->pluck('supervisor_user_id')
            ->toArray();

        $doctors = User::where('role_id', 2)
            ->where('is_active', true)
            ->whereNotIn('id', $excludedSupervisorIds)
            ->get(['id', 'full_name', 'email']);

        $tas = User::where('role_id', 3)
            ->where('is_active', true)
            ->whereNotIn('id', $excludedSupervisorIds)
            ->get(['id', 'full_name', 'email']);

        return response()->json([
            'success' => true,
            'data' => [
                'team'=>
                [
               'team_id' => $team->id,
               'project_title' => $proposal?->title,
               'project_category' => $proposal?->category,
               'leader' => $team->leader?->full_name,
               'members_count' => $team->members->where('status', 'active')->count(),
              ],
                'available_doctors' => $doctors,
                'available_tas' => $tas,
            ]
        ], 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'team_id' => 'required|exists:teams,id',
            'doctor_ids' => 'required|array|size:3',
            'doctor_ids.*' => 'required|exists:users,id',
            'ta_ids' => 'required|array|size:3',
            'ta_ids.*' => 'required|exists:users,id',
        ]);

        $admin = $request->user();

        return DB::transaction(function () use ($request, $admin) {
            $team = Team::findOrFail($request->team_id);

            if ($team->milestoneCommittee) {
                return response()->json([
                    'success' => false,
                    'message' => 'This team already has a milestone committee.'
                ], 400);
            }

            $excludedSupervisorIds = TeamSupervisor::where('team_id', $team->id)
                ->whereNull('ended_at')
                ->pluck('supervisor_user_id')
                ->toArray();

            $validDoctorsCount = User::whereIn('id', $request->doctor_ids)
                ->where('role_id', 2)
                ->where('is_active', true)
                ->whereNotIn('id', $excludedSupervisorIds)
                ->count();

            if ($validDoctorsCount !== 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'Milestone committee must include exactly 3 active doctors excluding team supervisors.'
                ], 422);
            }

            $validTasCount = User::whereIn('id', $request->ta_ids)
                ->where('role_id', 3)
                ->where('is_active', true)
                ->whereNotIn('id', $excludedSupervisorIds)
                ->count();

            if ($validTasCount !== 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'Milestone committee must include exactly 3 active teaching assistants excluding team supervisors.'
                ], 422);
            }

            $committee = MilestoneCommittee::create([
                'team_id' => $team->id,
                'created_by_admin_id' => $admin->id,
            ]);

            foreach ($request->doctor_ids as $doctorId) {
                MilestoneCommitteeMember::create([
                    'committee_id' => $committee->id,
                    'member_user_id' => $doctorId,
                    'member_role' => 'doctor',
                ]);
            }

            foreach ($request->ta_ids as $taId) {
                MilestoneCommitteeMember::create([
                    'committee_id' => $committee->id,
                    'member_user_id' => $taId,
                    'member_role' => 'ta',
                ]);
            }

$committee = $committee->fresh()->load('members.user');

$proposal = Proposal::where('team_id', $committee->team_id)
    ->where('status', 'approved')
    ->latest()
    ->first();

return response()->json([
    'success' => true,
    'message' => 'Milestone committee saved successfully.',
    'data' => [
        'id' => $committee->id,
        'team_id' => $committee->team_id,
        'project_title' => $proposal?->title,
        'project_category' => $proposal?->category,

        'members' => $committee->members->map(function ($member) {
            return [
                'id' => $member->user?->id,
                'name' => $member->user?->full_name,
                'role' => $member->member_role,
            ];
        })->values(),
    ],
], 201);
        });
    }
    public function update(Request $request, $id)
{
    $request->validate([
        'team_id' => 'nullable|exists:teams,id',

        'doctor_ids' => 'nullable|array|size:3',
        'doctor_ids.*' => 'required|distinct|exists:users,id',

        'ta_ids' => 'nullable|array|size:3',
        'ta_ids.*' => 'required|distinct|exists:users,id',
    ]);

    return DB::transaction(function () use ($request, $id) {
        $committee = MilestoneCommittee::with(['team', 'members'])->findOrFail($id);

        $teamId = $request->filled('team_id')
            ? $request->team_id
            : $committee->team_id;

        $team = Team::find($teamId);

        if (!$team) {
            return response()->json([
                'success' => false,
                'message' => 'Selected team not found.'
            ], 404);
        }

        if (
            $request->filled('team_id') &&
            MilestoneCommittee::where('team_id', $request->team_id)
                ->where('id', '!=', $committee->id)
                ->exists()
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Selected team already has a milestone committee.'
            ], 422);
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
                'success' => false,
                'message' => 'The same user cannot be selected more than once in the committee.'
            ], 422);
        }

        $excludedSupervisorIds = TeamSupervisor::where('team_id', $teamId)
            ->whereNull('ended_at')
            ->pluck('supervisor_user_id')
            ->toArray();

        $validDoctorsCount = User::whereIn('id', $doctorIds)
            ->where('role_id', 2)
            ->where('is_active', true)
            ->whereNotIn('id', $excludedSupervisorIds)
            ->count();

        if ($validDoctorsCount !== 3) {
            return response()->json([
                'success' => false,
                'message' => 'Milestone committee must include exactly 3 active doctors excluding team supervisors.'
            ], 422);
        }

        $validTasCount = User::whereIn('id', $taIds)
            ->where('role_id', 3)
            ->where('is_active', true)
            ->whereNotIn('id', $excludedSupervisorIds)
            ->count();

        if ($validTasCount !== 3) {
            return response()->json([
                'success' => false,
                'message' => 'Milestone committee must include exactly 3 active teaching assistants excluding team supervisors.'
            ], 422);
        }

        if ((int) $committee->team_id !== (int) $teamId) {
            $committee->update([
                'team_id' => $teamId
            ]);
        }

        if ($request->has('doctor_ids')) {
            $committee->members()
                ->where('member_role', 'doctor')
                ->delete();

            foreach ($doctorIds as $doctorId) {
                MilestoneCommitteeMember::create([
                    'committee_id' => $committee->id,
                    'member_user_id' => $doctorId,
                    'member_role' => 'doctor',
                ]);
            }
        }

        if ($request->has('ta_ids')) {
            $committee->members()
                ->where('member_role', 'ta')
                ->delete();

            foreach ($taIds as $taId) {
                MilestoneCommitteeMember::create([
                    'committee_id' => $committee->id,
                    'member_user_id' => $taId,
                    'member_role' => 'ta',
                ]);
            }
        }

$committee = $committee->fresh()->load('members.user');

$proposal = Proposal::where('team_id', $committee->team_id)
    ->where('status', 'approved')
    ->latest()
    ->first();

return response()->json([
    'success' => true,
    'message' => 'Milestone committee saved successfully.',
    'data' => [
        'id' => $committee->id,
        'team_id' => $committee->team_id,
        'project_title' => $proposal?->title,
        'project_category' => $proposal?->category,

        'members' => $committee->members->map(function ($member) {
            return [
                'id' => $member->user?->id,
                'name' => $member->user?->full_name,
                'role' => $member->member_role,
            ];
        })->values(),
    ],
], 200);
    });
}

    public function storeGrade(Request $request)
    {
        $request->validate([
            'team_id' => 'required|exists:teams,id',
            'milestone_id' => 'required|exists:milestones,id',
            'grade' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $doctor = $request->user();

        return DB::transaction(function () use ($request, $doctor) {
            $committee = MilestoneCommittee::where('team_id', $request->team_id)->first();

            if (!$committee) {
                return response()->json([
                    'success' => false,
                    'message' => 'This team does not have a milestone committee.'
                ], 404);
            }

            $isCommitteeDoctor = MilestoneCommitteeMember::where('committee_id', $committee->id)
                ->where('member_user_id', $doctor->id)
                ->where('member_role', 'doctor')
                ->exists();

            if (!$isCommitteeDoctor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only doctor committee members can grade this milestone.'
                ], 403);
            }

            $milestone = Milestone::findOrFail($request->milestone_id);

            if ((float) $request->grade > (float) $milestone->max_score) {
                return response()->json([
                    'success' => false,
                    'message' => "Grade cannot exceed milestone max score ({$milestone->max_score})."
                ], 422);
            }

            $hasSubmission = Submission::where('team_id', $request->team_id)
                ->where('milestone_id', $request->milestone_id)
                ->exists();

            if (!$hasSubmission) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot grade this team because no submission was uploaded for this milestone.'
                ], 422);
            }

            $existingGrade = MilestoneCommitteeGrade::where('team_id', $request->team_id)
                ->where('milestone_id', $request->milestone_id)
                ->where('project_course_id', $milestone->project_course_id)
                ->first();

            if ($existingGrade) {
                return response()->json([
                    'success' => false,
                    'message' => 'This milestone has already been graded. Only admin can update the grade.'
                ], 409);
            }

            $grade = MilestoneCommitteeGrade::create([
                'team_id' => $request->team_id,
                'milestone_id' => $request->milestone_id,
                'committee_id' => $committee->id,
                'project_course_id' => $milestone->project_course_id,
                'grade' => $request->grade,
                'graded_by_user_id' => $doctor->id,
                'graded_at' => now(),
                'notes' => $request->notes,
            ]);

return response()->json([
    'success' => true,
    'message' => 'Milestone grade submitted successfully.',
    'data' => [
        'id' => $grade->id,
        'team_id' => $grade->team_id,
        'milestone_id' => $grade->milestone_id,
        'project_course_id' => $grade->project_course_id,
        'grade' => $grade->grade,
        'graded_by' => [
            'id' => $doctor->id,
            'name' => $doctor->full_name,
        ],
        'graded_at' => $grade->graded_at,
        'notes' => $grade->notes,
    ]
], 201);
        });
    }

    public function saveGradeByAdmin(Request $request)
{
    $request->validate([
        'team_id' => 'required|exists:teams,id',
        'milestone_id' => 'required|exists:milestones,id',
        'grade' => 'required|numeric|min:0',
        'notes' => 'nullable|string|max:1000',
    ]);

    $admin = $request->user();

    return DB::transaction(function () use ($request, $admin) {
        $committee = MilestoneCommittee::where('team_id', $request->team_id)->first();

        if (!$committee) {
            return response()->json([
                'success' => false,
                'message' => 'This team does not have a milestone committee.'
            ], 404);
        }

        $milestone = Milestone::findOrFail($request->milestone_id);

        if ((float) $request->grade > (float) $milestone->max_score) {
            return response()->json([
                'success' => false,
                'message' => "Grade cannot exceed milestone max score ({$milestone->max_score})."
            ], 422);
        }

        $hasSubmission = Submission::where('team_id', $request->team_id)
            ->where('milestone_id', $request->milestone_id)
            ->exists();

        if (!$hasSubmission) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot grade this team because no submission was uploaded for this milestone.'
            ], 422);
        }

        $grade = MilestoneCommitteeGrade::updateOrCreate(
            [
                'team_id' => $request->team_id,
                'milestone_id' => $request->milestone_id,
                'project_course_id' => $milestone->project_course_id,
            ],
            [
                'committee_id' => $committee->id,
                'grade' => $request->grade,
                'graded_by_user_id' => $admin->id,
                'graded_at' => now(),
                'notes' => $request->notes,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Milestone grade saved successfully by admin.',
            'data' => [
                'id' => $grade->id,
                'team_id' => $grade->team_id,
                'milestone_id' => $grade->milestone_id,
                'project_course_id' => $grade->project_course_id,
                'grade' => $grade->grade,
                'graded_by_user_id' => $grade->graded_by_user_id,
                'graded_at' => $grade->graded_at,
                'notes' => $grade->notes,
            ]
        ], 200);
    });
}
}

