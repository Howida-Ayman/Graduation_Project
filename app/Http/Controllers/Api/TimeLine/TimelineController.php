<?php

namespace App\Http\Controllers\Api\TimeLine;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Milestone;
use App\Models\TeamMembership;
use App\Models\DefenseCommittee;
use App\Models\MilestoneCommitteeGrade;
use App\Models\ProjectCourse;
use App\Models\ProjectRule;
use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Models\SupervisorGrade;
use App\Models\TeamMilestonStatus;
use Illuminate\Http\Request;

class TimelineController extends Controller
{
    public function index(Request $request)
{
    $user = $request->user();

    $academicYear = AcademicYear::where('is_active', 1)->first();

    if (!$academicYear) {
        return response()->json([
            'success' => false,
            'message' => 'No active academic year found.'
        ], 404);
    }

    $enrollments = $user->enrollments()
        ->with('projectCourse')
        ->where('academic_year_id', $academicYear->id)
        ->whereIn('status', ['in_progress', 'passed'])
        ->get();

    if ($enrollments->isEmpty()) {
        return response()->json([
            'success' => false,
            'message' => 'No active project enrollments found.'
        ], 403);
    }

    /*
    لو Project II:
    هنعرض Project I + II
    */

    $hasProject2 = $enrollments->contains(function ($e) {
        return (int) $e->projectCourse?->order === 2;
    });

    if ($hasProject2) {
        $allowedCourseIds = $enrollments
            ->pluck('project_course_id');
    } else {
        $allowedCourseIds = $enrollments
            ->filter(fn ($e) => (int) $e->projectCourse?->order === 1)
            ->pluck('project_course_id');
    }

    $membership = TeamMembership::where('student_user_id', $user->id)
        ->where('academic_year_id', $academicYear->id)
        ->where('status', 'active')
        ->first();

    if (!$membership) {
        return response()->json([
            'success' => false,
            'message' => 'You are not in a team.'
        ], 403);
    }

    $teamId = $membership->team_id;

    $rules = ProjectRule::first();

    $milestones = Milestone::with([
            'requirements',
            'projectCourse'
        ])
        ->whereIn('project_course_id', $allowedCourseIds)
        ->where('is_active', true)
        ->orderBy('project_course_id')
        ->orderBy('phase_number')
        ->get();

    $statuses = TeamMilestonStatus::where('team_id', $teamId)
        ->whereIn('milestone_id', $milestones->pluck('id'))
        ->get()
        ->keyBy('milestone_id');

    $grades = MilestoneCommitteeGrade::where('team_id', $teamId)
        ->whereIn('milestone_id', $milestones->pluck('id'))
        ->get()
        ->keyBy('milestone_id');

    $supervisorGrades = SupervisorGrade::where('team_id', $teamId)
        ->whereIn('project_course_id', $allowedCourseIds)
        ->get()
        ->keyBy('project_course_id');

    $defenseCommittees = DefenseCommittee::with(['grade', 'members.member'])
        ->where('team_id', $teamId)
        ->whereIn('project_course_id', $allowedCourseIds)
        ->get()
        ->keyBy('project_course_id');

    $courses = ProjectCourse::whereIn('id', $allowedCourseIds)
        ->orderBy('order')
        ->get();

    $data = $courses->map(function ($course) use (
        $milestones,
        $statuses,
        $grades,
        $rules,
        $supervisorGrades,
        $defenseCommittees
    ) {

        $courseMilestones = $milestones
            ->where('project_course_id', $course->id)
            ->values();

        $milestonesData = $courseMilestones->map(function ($milestone) use (
            $statuses,
            $grades
        ) {

            $statusRow = $statuses->get($milestone->id);
            $gradeRow = $grades->get($milestone->id);

            return [
                'id' => $milestone->id,

                'title' => $milestone->title,

                'description' => $milestone->description,

                'requirements' => $milestone->requirements
                    ->pluck('requirement')
                    ->values(),

                'project_course' => [
                    'id' => $milestone->projectCourse?->id,
                    'name' => $milestone->projectCourse?->name,
                    'order' => $milestone->projectCourse?->order,
                ],

                'milestone_status' => $milestone->status,
                'start_date' => $milestone->start_date,

                'deadline' => $milestone->deadline,
                'max_score' => $milestone->max_score,
                
                'earned_score' => $gradeRow?->grade,
                'team_status' => $statusRow?->status,

            ];
        });

        $milestonesMaxScore = (float) $courseMilestones->sum('max_score');

        $supervisorMaxScore = (float) ($rules?->supervisor_max_score ?? 0);

        $defenseMaxScore = (float) ($rules?->defense_max_score ?? 0);

        $courseTotalMaxScore =
            $milestonesMaxScore
            + $supervisorMaxScore
            + $defenseMaxScore;

        $supervisorGrade = $supervisorGrades->get($course->id);

        $defenseCommittee = $defenseCommittees->get($course->id);

        return [

            'project_course' => [
                'id' => $course->id,
                'name' => $course->name,
                'order' => $course->order,
            ],

            'milestones' => $milestonesData->values(),

            'supervisor_evaluation' => [
                'max_score' => $supervisorMaxScore,
                'earned_score' => $supervisorGrade?->grade,
            ],

'final_discussion' => [
    'exists' => (bool) $defenseCommittee,

    'id' => $defenseCommittee?->id,

    'max_score' => $defenseMaxScore,

    'earned_score' => $defenseCommittee?->grade?->grade,

    'date' => $defenseCommittee?->scheduled_at
        ? \Carbon\Carbon::parse($defenseCommittee->scheduled_at)->format('Y-m-d')
        : null,

    'time' => $defenseCommittee?->scheduled_at
        ? \Carbon\Carbon::parse($defenseCommittee->scheduled_at)->format('H:i')
        : null,

    'location' => $defenseCommittee?->location,

    'status' => $defenseCommittee?->status,

    'doctors' => $defenseCommittee
        ? $defenseCommittee->members
            ->where('member_role', 'doctor')
            ->sortBy('seat_order')
            ->map(fn ($member) => [
                'id' => $member->member?->id,
                'name' => $member->member?->full_name,
                'email' => $member->member?->email,
                'seat_order' => $member->seat_order,
            ])
            ->values()
        : [],

    'assistants' => $defenseCommittee
        ? $defenseCommittee->members
            ->where('member_role', 'ta')
            ->sortBy('seat_order')
            ->map(fn ($member) => [
                'id' => $member->member?->id,
                'name' => $member->member?->full_name,
                'email' => $member->member?->email,
                'seat_order' => $member->seat_order,
            ])
            ->values()
        : [],
],

            'course_total' => [
                'milestones_max_score' => $milestonesMaxScore,

                'supervisor_max_score' => $supervisorMaxScore,

                'defense_max_score' => $defenseMaxScore,

                'total_max_score' => $courseTotalMaxScore,
            ],
        ];
    });

    return response()->json([
        'success' => true,

        'team_id' => $teamId,

        'academic_year' => [
            'id' => $academicYear->id,
            'code' => $academicYear->code,
        ],

        'data' => $data->values(),
    ], 200);
}

  

    public function show(Request $request, $milestoneId)
{
    $user = $request->user();

    $academicYear = AcademicYear::where('is_active', 1)->first();

    if (!$academicYear) {
        return response()->json([
            'success' => false,
            'message' => 'No active academic year found.'
        ], 404);
    }

    $membership = TeamMembership::where('student_user_id', $user->id)
        ->where('academic_year_id', $academicYear->id)
        ->where('status', 'active')
        ->first();

    if (!$membership) {
        return response()->json([
            'success' => false,
            'message' => 'You are not in a team.'
        ], 403);
    }

    $teamId = $membership->team_id;

    $milestone = Milestone::with([
            'requirements',
            'projectCourse'
        ])
        ->where('id', $milestoneId)
        ->where('is_active', true)
        ->first();

    if (!$milestone) {
        return response()->json([
            'success' => false,
            'message' => 'Milestone not found.'
        ], 404);
    }

    /*
    لو الطالب Project I
    مينفعش يشوف Milestones بتاعة Project II
    */

    $allowedCourseIds = $user->enrollments()
        ->where('academic_year_id', $academicYear->id)
        ->whereIn('status', ['in_progress', 'passed'])
        ->pluck('project_course_id');

    if (!$allowedCourseIds->contains($milestone->project_course_id)) {
        return response()->json([
            'success' => false,
            'message' => 'You are not allowed to access this milestone.'
        ], 403);
    }

    $teamStatus = TeamMilestonStatus::where('team_id', $teamId)
        ->where('milestone_id', $milestone->id)
        ->first();

    $grade = MilestoneCommitteeGrade::with('gradedBy')
        ->where('team_id', $teamId)
        ->where('milestone_id', $milestone->id)
        ->first();

    $submissions = Submission::with([
            'submitter',
            'files.feedbackBy'
        ])
        ->where('team_id', $teamId)
        ->where('milestone_id', $milestone->id)
        ->latest()
        ->get();

    return response()->json([

        'success' => true,

        'data' => [

            'id' => $milestone->id,

            'title' => $milestone->title,

            'description' => $milestone->description,

            'requirements' => $milestone->requirements
                ->pluck('requirement')
                ->values(),

            'project_course' => [
                'id' => $milestone->projectCourse?->id,
                'name' => $milestone->projectCourse?->name,
                'order' => $milestone->projectCourse?->order,
            ],

            'status' => $milestone->status,

            'team_status' => $teamStatus?->status,

            'max_score' => $milestone->max_score,

            'earned_score' => $grade?->grade,

            'graded_at' => $grade?->graded_at,
            'graded_by' => $grade ? [
                'id' => $grade->gradedBy?->id,
                'name' => $grade->gradedBy?->full_name,
             ] : null,

            'admin_note' => $milestone->notes,

            'start_date' => $milestone->start_date,

            'deadline' => $milestone->deadline,

            'submissions' => $submissions->map(function ($submission) {

                return [

                    'id' => $submission->id,

                    'submitted_at' => $submission->submitted_at,

                    'submitted_by' => [
                        'id' => $submission->submitter?->id,
                        'name' => $submission->submitter?->full_name,
                    ],

                    'notes' => $submission->notes,

'files' => $submission->files->map(function ($file) {
    return [
        'id' => $file->id,
        'file_name' => $file->original_name,
        'file_url' => $file->file_url,
        'uploaded_at' => $file->uploaded_at,

        'feedback' => $file->feedback ? [
            'text' => $file->feedback,
            'by' => $file->feedbackBy ? [
                'id' => $file->feedbackBy?->id,
                'name' => $file->feedbackBy?->full_name,
            ] : null,
            'at' => $file->feedback_at,
        ] : null,
    ];
})->values(),
                ];
            })->values(),
        ]

    ], 200);
}
}