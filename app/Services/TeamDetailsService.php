<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\DefenseCommittee;
use App\Models\Milestone;
use App\Models\MilestoneCommitteeGrade;
use App\Models\ProjectCourse;
use App\Models\ProjectRule;
use App\Models\Submission;
use App\Models\SupervisorGrade;
use App\Models\Team;
use Illuminate\Support\Carbon;

class TeamDetailsService
{
    public function buildResponse(Team $team): array
    {
        $today = Carbon::now();

        $activeAcademicYear = AcademicYear::where('is_active', 1)->first();

        $project = $team->graduationProject;
        $proposal = $project?->proposal;

        $doctor = $team->currentSupervisors
            ->firstWhere('pivot.supervisor_role', 'doctor');

        $ta = $team->currentSupervisors
            ->firstWhere('pivot.supervisor_role', 'ta');

        $members = $team->members
            ->where('status', 'active')
            ->map(function ($member) {
                return [
                    'id' => $member->student_user_id,
                    'name' => $member->user?->full_name,
                    'email' => $member->user?->email,
                    'track_name' => $member->user?->track_name,
                    'role_in_team' => $member->role_in_team,
                    'image' => $member->user?->profile_image_url,
                ];
            })->values();

        $projectCourses = ProjectCourse::orderBy('order')->get();

        $teamMilestoneStatuses = $team->teamMilestonestatus->keyBy('milestone_id');

        $milestoneCommittee = $team->milestoneCommittee?->load('members.user');

        $milestoneCommitteeData = $milestoneCommittee ? [
            'id' => $milestoneCommittee->id,
            'doctors' => $milestoneCommittee->members
                ->where('member_role', 'doctor')
                ->map(function ($member) {
                    return [
                        'id' => $member->user?->id,
                        'name' => $member->user?->full_name,
                        'email' => $member->user?->email,
                        'role' => 'doctor',
                    ];
                })->values(),
            'tas' => $milestoneCommittee->members
                ->where('member_role', 'ta')
                ->map(function ($member) {
                    return [
                        'id' => $member->user?->id,
                        'name' => $member->user?->full_name,
                        'email' => $member->user?->email,
                        'role' => 'ta',
                    ];
                })->values(),
        ] : null;

        $rules = ProjectRule::first();

        $coursesData = $projectCourses->map(function ($course) use ($team, $today, $teamMilestoneStatuses, $rules) {
            $milestones = Milestone::where('project_course_id', $course->id)
                ->orderBy('phase_number')
                ->get();

            $milestoneGrades = MilestoneCommitteeGrade::with('gradedBy')
                ->where('team_id', $team->id)
                ->where('project_course_id', $course->id)
                ->get()
                ->keyBy('milestone_id');

            $supervisorGrade = SupervisorGrade::with('gradedBy')
                ->where('team_id', $team->id)
                ->where('project_course_id', $course->id)
                ->first();

            $defenseCommittee = DefenseCommittee::with([
                    'projectCourse',
                    'members.member',
                    'grade',
                ])
                ->where('team_id', $team->id)
                ->where('project_course_id', $course->id)
                ->first();

            $currentMilestone = $milestones->first(function ($milestone) use ($today) {
                return Carbon::parse($milestone->start_date) <= $today
                    && Carbon::parse($milestone->deadline) >= $today;
            });

            if (!$currentMilestone) {
                $currentMilestone = $milestones->first();
            }

            $currentMilestoneStatus = $currentMilestone
                ? $teamMilestoneStatuses->get($currentMilestone->id)
                : null;

            $milestonesData = $milestones->map(function ($milestone) use ($team, $teamMilestoneStatuses, $milestoneGrades) {
                $statusRow = $teamMilestoneStatuses->get($milestone->id);
                $gradeRow = $milestoneGrades->get($milestone->id);

                $latestSubmission = Submission::with(['files', 'submitter'])
                    ->where('team_id', $team->id)
                    ->where('milestone_id', $milestone->id)
                    ->latest()
                    ->first();

                return [
                    'id' => $milestone->id,
                    'title' => $milestone->title,
                    'phase_number' => $milestone->phase_number,
                    'start_date' => $milestone->start_date,
                    'deadline' => $milestone->deadline,
                    'max_score' => $milestone->max_score,
                    'milestone_status' => $milestone->status,
                    'is_open' => (bool) $milestone->is_open,

                    'team_status' => $statusRow?->status,
                    'status_updated_at' => $statusRow?->updated_at,

                    'grade' => $gradeRow ? [
                        'id' => $gradeRow->id,
                        'grade' => $gradeRow->grade,
                        'graded_by' => [
                            'id' => $gradeRow->gradedBy?->id,
                            'name' => $gradeRow->gradedBy?->full_name,
                        ],
                        'graded_at' => $gradeRow->graded_at,
                        'notes' => $gradeRow->notes,
                    ] : null,

                    'latest_submission' => $latestSubmission ? [
                        'id' => $latestSubmission->id,
                        'submitted_by' => [
                            'id' => $latestSubmission->submitter?->id,
                            'name' => $latestSubmission->submitter?->full_name,
                        ],
                        'submitted_at' => $latestSubmission->submitted_at,
                        'notes' => $latestSubmission->notes,
                        'files' => $latestSubmission->files->map(function ($file) {
                            return [
                                'id' => $file->id,
                                'file_name' => $file->original_name,
                                'file_url' => $file->file_url,
                                'uploaded_at' => $file->uploaded_at ?? $file->created_at,
                            ];
                        })->values(),
                    ] : null,
                ];
            })->values();

            $milestoneGradesTotal = (float) $milestoneGrades->sum('grade');
            $supervisorGradeValue = (float) ($supervisorGrade?->grade ?? 0);
            $defenseGradeValue = (float) ($defenseCommittee?->grade?->grade ?? 0);

            $totalScore = $milestoneGradesTotal + $supervisorGradeValue + $defenseGradeValue;

            $passingPercentage = (float) ($rules?->passing_percentage ?? 0);

            $resultStatus = null;

            if ($supervisorGrade || $defenseCommittee?->grade || $milestoneGrades->isNotEmpty()) {
                $resultStatus = $totalScore >= $passingPercentage ? 'passed' : 'failed';
            }

            return [
                'project_course' => [
                    'id' => $course->id,
                    'name' => $course->name,
                    'order' => $course->order,
                ],

                'current_milestone' => $currentMilestone ? [
                    'id' => $currentMilestone->id,
                    'title' => $currentMilestone->title,
                    'deadline' => $currentMilestone->deadline,
                    'team_status' => $currentMilestoneStatus?->status,
                ] : null,

                'milestones' => $milestonesData,

                'supervisor_grade' => $supervisorGrade ? [
                    'id' => $supervisorGrade->id,
                    'grade' => $supervisorGrade->grade,
                    'graded_by' => [
                        'id' => $supervisorGrade->gradedBy?->id,
                        'name' => $supervisorGrade->gradedBy?->full_name,
                    ],
                    'graded_at' => $supervisorGrade->graded_at,
                    'notes' => $supervisorGrade->notes,
                ] : null,

                'defense_committee' => $defenseCommittee ? [
                    'id' => $defenseCommittee->id,
                    'scheduled_at' => $defenseCommittee->scheduled_at,
                    'location' => $defenseCommittee->location,
                    'status' => $defenseCommittee->status,

                    'doctors' => $defenseCommittee->members
                        ->where('member_role', 'doctor')
                        ->sortBy('seat_order')
                        ->map(function ($member) {
                            return [
                                'id' => $member->member?->id,
                                'name' => $member->member?->full_name,
                                'seat_order' => $member->seat_order,
                            ];
                        })->values(),

                    'assistant' => optional(
                        $defenseCommittee->members->firstWhere('member_role', 'ta')
                    )->member ? [
                        'id' => $defenseCommittee->members->firstWhere('member_role', 'ta')?->member?->id,
                        'name' => $defenseCommittee->members->firstWhere('member_role', 'ta')?->member?->full_name,
                    ] : null,

                    'grade' => $defenseCommittee->grade ? [
                        'id' => $defenseCommittee->grade->id,
                        'grade' => $defenseCommittee->grade->grade,
                        'graded_at' => $defenseCommittee->grade->entered_at,
                    ] : null,
                ] : null,

                'score_summary' => [
                    'milestone_committee_total' => $milestoneGradesTotal,
                    'supervisor_grade' => $supervisorGradeValue,
                    'defense_grade' => $defenseGradeValue,
                    'total_score' => $totalScore,
                    'passing_percentage' => $passingPercentage,
                    'result_status' => $resultStatus,
                ],
            ];
        })->values();

        $submittedFiles = $team->submissions
            ->flatMap(function ($submission) {
                return $submission->files->map(function ($file) use ($submission) {
                    return [
                        'id' => $file->id,
                        'file_name' => $file->original_name,
                        'milestone' => $submission->milestone?->title,
                        'project_course' => [
                            'id' => $submission->milestone?->projectCourse?->id,
                            'name' => $submission->milestone?->projectCourse?->name,
                            'order' => $submission->milestone?->projectCourse?->order,
                        ],
                        'uploaded_at' => $file->uploaded_at ?? $file->created_at,
                    ];
                });
            })
            ->sortByDesc('uploaded_at')
            ->values();

        return [
            'message' => 'Team details retrieved successfully.',

            'academic_year' => $activeAcademicYear ? [
                'id' => $activeAcademicYear->id,
                'code' => $activeAcademicYear->code,
            ] : null,

            'team' => [
                'id' => $team->id,
                'department' => [
                    'id' => $team->department?->id,
                    'name' => $team->department?->name,
                ],
                'leader' => [
                    'id' => $team->leader?->id,
                    'name' => $team->leader?->full_name,
                    'email' => $team->leader?->email,
                ],
                'members_count' => $members->count(),
                'members' => $members,
            ],

            'project' => [
                'title' => $proposal?->title,
                'description' => $proposal?->description,
                'problem_statement' => $proposal?->problem_statement,
                'solution' => $proposal?->solution,
                'image_url' => $project?->image_url ?? $proposal?->image_url,
                'file_url' => $proposal?->attachment_file,
                'category' => $proposal?->category,
                'technologies' => $proposal?->technologies,
            ],

            'supervisors' => [
                'doctor' => $doctor ? [
                    'id' => $doctor->id,
                    'name' => $doctor->full_name,
                    'email' => $doctor->email,
                ] : null,
                'ta' => $ta ? [
                    'id' => $ta->id,
                    'name' => $ta->full_name,
                    'email' => $ta->email,
                ] : null,
            ],

            'milestone_committee' => $milestoneCommitteeData,

            'courses' => $coursesData,

            'submitted_files' => $submittedFiles,
        ];
    }
}