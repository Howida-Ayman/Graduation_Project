<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\Milestone;
use App\Models\ProjectCourse;
use App\Models\Team;
use App\Models\TeamMilestonStatus;
use App\Models\User;
use App\Services\TeamDetailsService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TeamController extends Controller
{
    public function allteams(Request $request)
    {
        $today = Carbon::now();

        $academicYear = AcademicYear::where('is_active', 1)->first();

        if (!$academicYear) {
            return response()->json([
                'message' => 'No active academic year found.'
            ], 404);
        }

        $request->validate([
            'project_course_id' => 'nullable|exists:project_courses,id',
            'milestone_id' => 'nullable|exists:milestones,id',
        ]);

        $projectCourse = null;

        if ($request->filled('project_course_id')) {
            $projectCourse = ProjectCourse::find($request->project_course_id);
        }

        if ($request->filled('milestone_id')) {
            $selectedMilestone = Milestone::find($request->milestone_id);

            if (!$selectedMilestone) {
                return response()->json([
                    'message' => 'Milestone not found.'
                ], 404);
            }

            $projectCourse = $selectedMilestone->projectCourse;
        } else {
            $milestoneQuery = Milestone::query();

            if ($projectCourse) {
                $milestoneQuery->where('project_course_id', $projectCourse->id);
            }

            $selectedMilestone = $milestoneQuery
                ->where('start_date', '<=', $today)
                ->where('deadline', '>=', $today)
                ->orderBy('phase_number')
                ->first();

            if (!$selectedMilestone) {
                $selectedMilestone = $milestoneQuery
                    ->orderBy('phase_number')
                    ->first();
            }
        }

        if (!$selectedMilestone) {
            return response()->json([
                'message' => 'No milestones found.'
            ], 404);
        }

        $isCurrentMilestone = $today->between(
            Carbon::parse($selectedMilestone->start_date),
            Carbon::parse($selectedMilestone->deadline)
        );

        $isPastDeadline = Carbon::parse($selectedMilestone->deadline)->isPast() && !$isCurrentMilestone;
        $isFutureMilestone = Carbon::parse($selectedMilestone->start_date)->isFuture();

        $summaryTeams = Team::where('academic_year_id', $academicYear->id)
            ->whereHas('members.user.enrollments', function ($q) use ($academicYear, $selectedMilestone) {
                $q->where('academic_year_id', $academicYear->id)
                    ->where('project_course_id', $selectedMilestone->project_course_id)
                    ->where('status', 'in_progress');
            })
            ->with([
                'milestones' => function ($query) use ($selectedMilestone) {
                    $query->where('milestones.id', $selectedMilestone->id);
                },
            ])
            ->get();

        $totalTeams = $summaryTeams->count();

        $onTrackCount = $summaryTeams->filter(function ($team) {
            $milestone = $team->milestones->first();
            return $milestone && $milestone->pivot->status === 'on_track';
        })->count();

        $pendingSubmissionCount = $summaryTeams->filter(function ($team) {
            $milestone = $team->milestones->first();
            return $milestone && $milestone->pivot->status === 'pending_submission';
        })->count();

        $delayedCount = $summaryTeams->filter(function ($team) {
            $milestone = $team->milestones->first();
            return $milestone && $milestone->pivot->status === 'delayed';
        })->count();

        $previousMilestonesIds = Milestone::where('project_course_id', $selectedMilestone->project_course_id)
            ->where('phase_number', '<', $selectedMilestone->phase_number)
            ->pluck('id');

        $previousDelayedTotal = 0;

        if ($previousMilestonesIds->isNotEmpty()) {
            $previousDelayedTotal = TeamMilestonStatus::whereIn('milestone_id', $previousMilestonesIds)
                ->where('status', 'delayed')
                ->distinct('team_id')
                ->count('team_id');
        }

        if ($isCurrentMilestone) {
            $summary = [
                'total_teams' => $totalTeams,
                'on_track' => $onTrackCount,
                'pending_submission' => $pendingSubmissionCount,
                'previous_milestones_delayed_total' => $previousDelayedTotal,
            ];
        } elseif ($isPastDeadline) {
            $summary = [
                'total_teams' => $totalTeams,
                'on_track' => $onTrackCount,
                'delayed' => $delayedCount,
            ];
        } else {
            $summary = [
                'total_teams' => $totalTeams,
                'pending_submission' => $pendingSubmissionCount,
            ];
        }

        $teamsQuery = Team::where('academic_year_id', $academicYear->id)
            ->whereHas('members.user.enrollments', function ($q) use ($academicYear, $selectedMilestone) {
                $q->where('academic_year_id', $academicYear->id)
                    ->where('project_course_id', $selectedMilestone->project_course_id)
                    ->where('status', 'in_progress');
            })
            ->with([
                'department',
                'leader',
                'members.user',
                'graduationProject.proposal',
                'currentSupervisors',
                'milestones' => function ($query) use ($selectedMilestone) {
                    $query->where('milestones.id', $selectedMilestone->id);
                },
            ]);

        if ($request->filled('search')) {
            $search = $request->search;

            $teamsQuery->where(function ($q) use ($search) {
                $q->whereHas('graduationProject.proposal', function ($sub) use ($search) {
                    $sub->where('title', 'like', "%{$search}%");
                })->orWhereHas('members.user', function ($sub) use ($search) {
                    $sub->where('full_name', 'like', "%{$search}%");
                });
            });
        }

        if ($request->filled('department_id')) {
            $teamsQuery->where('department_id', $request->department_id);
        }

        if ($request->filled('doctor_id')) {
            $doctorId = $request->doctor_id;

            $teamsQuery->whereHas('currentSupervisors', function ($q) use ($doctorId) {
                $q->where('users.id', $doctorId)
                    ->where('team_supervisors.supervisor_role', 'doctor')
                    ->whereNull('team_supervisors.ended_at');
            });
        }

        $allowedStatuses = [];

        if ($isCurrentMilestone) {
            $allowedStatuses = ['on_track', 'pending_submission'];
        } elseif ($isPastDeadline) {
            $allowedStatuses = ['on_track', 'delayed'];
        } elseif ($isFutureMilestone) {
            $allowedStatuses = ['pending_submission'];
        }

        if ($request->filled('status') && in_array($request->status, $allowedStatuses)) {
            $status = $request->status;

            $teamsQuery->whereHas('milestones', function ($q) use ($selectedMilestone, $status) {
                $q->where('milestones.id', $selectedMilestone->id)
                    ->where('team_milestone_status.status', $status);
            });
        }

        $teams = $teamsQuery->get();

        $formattedTeams = $teams->map(function ($team) {
            $project = $team->graduationProject;
            $proposal = $project?->proposal;

            $doctor = $team->currentSupervisors
                ->firstWhere('pivot.supervisor_role', 'doctor');

            $ta = $team->currentSupervisors
                ->firstWhere('pivot.supervisor_role', 'ta');

            $selectedMilestoneData = $team->milestones->first();
            $teamStatus = $selectedMilestoneData?->pivot?->status;

            return [
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

                'members_count' => $team->members->where('status', 'active')->count(),

                'project' => [
                        'title' => $proposal?->title,
                        'description' => $proposal?->description,
                        'problem_statement' => $proposal?->problem_statement,
                        'solution' => $proposal?->solution,
                        'category' => $proposal?->category,
                        'technologies' => $proposal?->technologies,
                        'image_url' => $project?->image_url ?? $proposal?->image_url,
                        'file_url' => $proposal?->attachment_file,
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

                'selected_milestone' => [
                    'id' => $selectedMilestoneData?->id,
                    'title' => $selectedMilestoneData?->title,
                    'project_course_id' => $selectedMilestoneData?->project_course_id,
                    'phase_number' => $selectedMilestoneData?->phase_number,
                    'deadline' => $selectedMilestoneData?->deadline,
                    'team_status' => $teamStatus,
                ],
            ];
        });

        $milestones = Milestone::where('project_course_id', $selectedMilestone->project_course_id)
            ->orderBy('phase_number')
            ->get()
            ->map(function ($milestone) use ($today) {
                $isCurrent = $today->between(
                    Carbon::parse($milestone->start_date),
                    Carbon::parse($milestone->deadline)
                );

                $isPast = Carbon::parse($milestone->deadline)->isPast() && !$isCurrent;
                $isFuture = Carbon::parse($milestone->start_date)->isFuture();

                $statusOptions = [];

                if ($isCurrent) {
                    $statusOptions = [
                        ['value' => 'on_track', 'label' => 'On Track'],
                        ['value' => 'pending_submission', 'label' => 'Pending Submission'],
                    ];
                } elseif ($isPast) {
                    $statusOptions = [
                        ['value' => 'on_track', 'label' => 'On Track'],
                        ['value' => 'delayed', 'label' => 'Delayed'],
                    ];
                } elseif ($isFuture) {
                    $statusOptions = [
                        ['value' => 'pending_submission', 'label' => 'Pending Submission'],
                    ];
                }

                return [
                    'id' => $milestone->id,
                    'project_course_id' => $milestone->project_course_id,
                    'title' => $milestone->title,
                    'phase_number' => $milestone->phase_number,
                    'deadline' => $milestone->deadline,
                    'is_current' => $isCurrent,
                    'is_past_deadline' => $isPast,
                    'is_future' => $isFuture,
                    'status_filter_options' => $statusOptions,
                ];
            });

        if ($isCurrentMilestone) {
            $selectedMilestoneStatusOptions = [
                ['value' => 'on_track', 'label' => 'On Track'],
                ['value' => 'pending_submission', 'label' => 'Pending Submission'],
            ];
        } elseif ($isPastDeadline) {
            $selectedMilestoneStatusOptions = [
                ['value' => 'on_track', 'label' => 'On Track'],
                ['value' => 'delayed', 'label' => 'Delayed'],
            ];
        } else {
            $selectedMilestoneStatusOptions = [
                ['value' => 'pending_submission', 'label' => 'Pending Submission'],
            ];
        }

        return response()->json([
            'message' => 'Teams retrieved successfully.',

            'academic_year' => [
                'id' => $academicYear->id,
                'code' => $academicYear->code,
            ],

            'selected_project_course' => [
                'id' => $selectedMilestone->projectCourse?->id,
                'name' => $selectedMilestone->projectCourse?->name,
                'order' => $selectedMilestone->projectCourse?->order,
            ],

            'milestones' => $milestones,

            'selected_milestone_info' => [
                'id' => $selectedMilestone->id,
                'project_course_id' => $selectedMilestone->project_course_id,
                'title' => $selectedMilestone->title,
                'phase_number' => $selectedMilestone->phase_number,
                'start_date' => $selectedMilestone->start_date,
                'deadline' => $selectedMilestone->deadline,
                'global_status' => $selectedMilestone->status,
                'is_open' => $selectedMilestone->is_open,
                'is_current' => $isCurrentMilestone,
                'is_past_deadline' => $isPastDeadline,
                'is_future' => $isFutureMilestone,
                'status_filter_options' => $selectedMilestoneStatusOptions,
            ],

            'summary' => $summary,

            'applied_filters' => [
                'project_course_id' => $selectedMilestone->project_course_id,
                'search' => $request->search,
                'department_id' => $request->department_id,
                'status' => $request->status,
                'doctor_id' => $request->doctor_id,
            ],

            'filter_options' => [
                'project_courses' => ProjectCourse::orderBy('order')->get(['id', 'name', 'order']),
                'departments' => Department::where('is_active', 1)->get(['id', 'name']),
                'doctors' => User::where('role_id', 2)->get(['id', 'full_name']),
            ],

            'data' => $formattedTeams->values(),
        ], 200);
    }

    public function viewTeam($teamId, TeamDetailsService $teamDetailsService)
    {
        $team = Team::with([
            'department',
            'leader',
            'members.user',
            'graduationProject.proposal',
            'currentSupervisors',
            'teamMilestonestatus',
            'teamMilestonestatus.milestone.projectCourse',
            'submissions.files',
            'submissions.milestone.projectCourse',
            'submissions.submitter',
            'milestoneCommittee.members.user',
        ])->find($teamId);

        if (!$team) {
            return response()->json([
                'message' => 'Team not found.'
            ], 404);
        }

        return response()->json(
            $teamDetailsService->buildResponse($team),
            200
        );
    }
}