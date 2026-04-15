<?php

namespace App\Http\Controllers\Api\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Milestone;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Services\TeamDetailsService;

class TeamManagementController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $now = now();

        $search = trim((string) $request->query('search', ''));
        $departmentId = $request->query('department_id');
        $statusFilter = $request->query('status');
        $counterpartId = $request->query('counterpart_id');

        $viewerRole = match ((int) $user->role_id) {
            2 => 'doctor',
            3 => 'ta',
            default => 'unknown',
        };

        $currentMilestone = Milestone::query()
            ->where('is_active', true)
            ->where('start_date', '<=', $now)
            ->where('deadline', '>=', $now)
            ->orderBy('phase_number')
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Base query: كل تيمات المشرف في السنة الفعالة فقط
        |--------------------------------------------------------------------------
        */
        $baseTeamsQuery = Team::query()
            ->activeYear()
            ->whereHas('currentSupervisors', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });

        $baseTeams = (clone $baseTeamsQuery)->with([
            'department',
            'academicYear',
            'leader',
            'activeMembers.student',
            'supervisors',
            'graduationProject.proposal',
            'teamMilestonestatus.milestone',
            'submissions.files',
            'meetings',
        ])->get();

        $baseTeamIds = $baseTeams->pluck('id');

        /*
        |--------------------------------------------------------------------------
        | Filtered query: خاصة بالـ projects listing فقط
        |--------------------------------------------------------------------------
        */
        $teamsQuery = clone $baseTeamsQuery;

        if (!empty($departmentId)) {
            $teamsQuery->where('department_id', $departmentId);
        }

        if ($search !== '') {
            $teamsQuery->where(function ($query) use ($search) {
                $query->whereHas('graduationProject.proposal', function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%");
                })
                ->orWhereHas('activeMembers.student', function ($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%");
                })
                ->orWhereHas('supervisors', function ($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%");
                });
            });
        }

        if (!empty($counterpartId)) {
            if ((int) $user->role_id === 2) {
                // logged in user is doctor => filter by TA
                $teamsQuery->whereHas('supervisors', function ($q) use ($counterpartId) {
                    $q->where('users.id', $counterpartId)
                      ->where('team_supervisors.supervisor_role', 'ta');
                });
            } elseif ((int) $user->role_id === 3) {
                // logged in user is TA => filter by doctor
                $teamsQuery->whereHas('supervisors', function ($q) use ($counterpartId) {
                    $q->where('users.id', $counterpartId)
                      ->where('team_supervisors.supervisor_role', 'doctor');
                });
            }
        }

        $teams = $teamsQuery->with([
            'department',
            'academicYear',
            'leader',
            'activeMembers.student',
            'supervisors',
            'graduationProject.proposal',
            'teamMilestonestatus.milestone',
            'submissions.files',
            'meetings',
        ])->get();

        /*
        |--------------------------------------------------------------------------
        | Mapper موحد
        |--------------------------------------------------------------------------
        */
        $mapProjects = function ($teamsCollection) use ($currentMilestone) {
            return $teamsCollection->map(function ($team) use ($currentMilestone) {
                $doctorSupervisor = $team->supervisors
                    ->first(fn ($sup) => $sup->pivot?->supervisor_role === 'doctor');

                $taSupervisor = $team->supervisors
                    ->first(fn ($sup) => $sup->pivot?->supervisor_role === 'ta');

                $project = $team->graduationProject;
                $proposal = $project?->proposal;

                $currentMilestoneStatus = null;
                $currentMilestoneData = null;

                if ($currentMilestone) {
                    $currentMilestoneStatus = $team->teamMilestonestatus
                        ->firstWhere('milestone_id', $currentMilestone->id);

                    $currentMilestoneData = [
                        'id' => $currentMilestone->id,
                        'title' => $currentMilestone->title,
                        'phase_number' => $currentMilestone->phase_number,
                        'deadline' => optional($currentMilestone->deadline)?->format('Y-m-d H:i:s'),
                        'team_status' => $currentMilestoneStatus?->status ?? 'pending_submission',
                        'grade' => $currentMilestoneStatus?->milestone_grade,
                    ];
                }

                $hasPreviousDelay = $team->teamMilestonestatus->contains(function ($status) use ($currentMilestone) {
                    if ($status->status !== 'delayed') {
                        return false;
                    }

                    if (! $currentMilestone || ! $status->milestone) {
                        return true;
                    }

                    return $status->milestone->phase_number < $currentMilestone->phase_number;
                });

                $overallStatus = $hasPreviousDelay
                    ? 'delayed'
                    : ($currentMilestoneData['team_status'] ?? 'pending_submission');

                $latestSubmission = $team->submissions
                    ->sortByDesc('submitted_at')
                    ->first();

                $latestFile = $latestSubmission?->files
                    ? $latestSubmission->files->sortByDesc('uploaded_at')->first()
                    : null;

                $cardStatus = 'nothing';

                if ($currentMilestone) {
                    $currentSubmission = $team->submissions
                        ->where('milestone_id', $currentMilestone->id)
                        ->sortByDesc('submitted_at')
                        ->first();

                    $currentFile = $currentSubmission?->files
                        ? $currentSubmission->files->sortByDesc('uploaded_at')->first()
                        : null;

                    $hasGrade = ! is_null($currentMilestoneStatus?->milestone_grade);
                    $hasFeedback = ! empty($currentFile?->feedback);

                    if ($hasGrade && $hasFeedback) {
                        $cardStatus = 'completed';
                    } elseif ($hasGrade && ! $hasFeedback) {
                        $cardStatus = 'pending_feedback';
                    } elseif (! $hasGrade && $hasFeedback) {
                        $cardStatus = 'pending_grades';
                    }
                }

                return [
                    'team_id' => $team->id,
                    'overall_status' => $overallStatus,
                    'card_status' => $cardStatus,

                    'department' => [
                        'id' => $team->department?->id,
                        'name' => $team->department?->name,
                    ],

                    'academic_year' => [
                        'id' => $team->academicYear?->id,
                        'code' => $team->academicYear?->code,
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

                    'doctor_supervisor' => [
                        'id' => $doctorSupervisor?->id,
                        'name' => $doctorSupervisor?->full_name,
                        'image' => $doctorSupervisor?->profile_image_url,
                    ],

                    'ta_supervisor' => [
                        'id' => $taSupervisor?->id,
                        'name' => $taSupervisor?->full_name,
                        'image' => $taSupervisor?->profile_image_url,
                    ],

                    'members' => $team->activeMembers->map(function ($member) {
                        return [
                            'id' => $member->student?->id,
                            'name' => $member->student?->full_name,
                            'role' => $member->role_in_team,
                            'image' => $member->student?->profile_image_url,
                        ];
                    })->values(),

                    'last_submission' => [
                        'submitted_at' => $latestSubmission?->submitted_at?->format('Y-m-d H:i:s'),
                        'file_name' => $latestFile?->original_name,
                        'file_uploaded_at' => $latestFile?->uploaded_at?->format('Y-m-d H:i:s'),
                    ],

                    'current_milestone' => $currentMilestoneData,
                ];
            })->values();
        };

        /*
        |--------------------------------------------------------------------------
        | Projects list (filtered)
        |--------------------------------------------------------------------------
        */
        $projects = $mapProjects($teams);

        if (!empty($statusFilter)) {
            $projects = $projects->where('overall_status', $statusFilter)->values();
        }

        /*
        |--------------------------------------------------------------------------
        | Statistics: السنة الفعالة فقط
        |--------------------------------------------------------------------------
        */
        $allProjectsForStats = $mapProjects($baseTeams);

        $statistics = [
            'total_teams' => $allProjectsForStats->count(),
            'on_track' => $allProjectsForStats->where('overall_status', 'on_track')->count(),
            'delayed' => $allProjectsForStats->where('overall_status', 'delayed')->count(),
            'meetings_this_week' => $baseTeams
                ->flatMap->meetings
                ->filter(function ($meeting) {
                    return Carbon::parse($meeting->scheduled_at)->isCurrentWeek();
                })
                ->count(),
        ];

        /*
        |--------------------------------------------------------------------------
        | Filter options: السنة الفعالة فقط
        |--------------------------------------------------------------------------
        */
        $departmentOptions = $baseTeams
            ->map(function ($team) {
                return [
                    'id' => $team->department?->id,
                    'name' => $team->department?->name,
                ];
            })
            ->filter(fn ($item) => !is_null($item['id']))
            ->unique('id')
            ->values();

        if ((int) $user->role_id === 2) {
            $counterpartOptions = $baseTeams
                ->flatMap(function ($team) {
                    return $team->supervisors
                        ->filter(fn ($sup) => $sup->pivot?->supervisor_role === 'ta')
                        ->map(function ($sup) {
                            return [
                                'id' => $sup->id,
                                'name' => $sup->full_name,
                                'image' => $sup->profile_image_url,
                            ];
                        });
                })
                ->unique('id')
                ->values();

            $counterpartLabel = 'teaching_assistants';
        } else {
            $counterpartOptions = $baseTeams
                ->flatMap(function ($team) {
                    return $team->supervisors
                        ->filter(fn ($sup) => $sup->pivot?->supervisor_role === 'doctor')
                        ->map(function ($sup) {
                            return [
                                'id' => $sup->id,
                                'name' => $sup->full_name,
                                'image' => $sup->profile_image_url,
                            ];
                        });
                })
                ->unique('id')
                ->values();

            $counterpartLabel = 'doctors';
        }

        $statusOptions = [
            ['value' => 'pending_submission', 'label' => 'Pending Submission'],
            ['value' => 'on_track', 'label' => 'On Track'],
            ['value' => 'delayed', 'label' => 'Delayed'],
        ];

        /*
        |--------------------------------------------------------------------------
        | Side widgets: السنة الفعالة فقط
        |--------------------------------------------------------------------------
        */
        $upcomingMeetings = $baseTeams
            ->flatMap(function ($team) {
                return $team->meetings->map(function ($meeting) use ($team) {
                    return [
                        'id' => $meeting->id,
                        'scheduled_at' => Carbon::parse($meeting->scheduled_at)->format('Y-m-d H:i:s'),
                        'team_id' => $team->id,
                        'project_title' => $team->graduationProject?->proposal?->title,
                    ];
                });
            })
            ->filter(fn ($meeting) => Carbon::parse($meeting['scheduled_at'])->isFuture())
            ->sortBy('scheduled_at')
            ->take(10)
            ->values();

        $pendingFeedback = $allProjectsForStats
            ->where('card_status', 'pending_feedback')
            ->map(function ($project) {
                return [
                    'team_id' => $project['team_id'],
                    'project_title' => $project['project']['title'],
                ];
            })
            ->values();

        $recentlyGraded = $baseTeams
            ->flatMap(function ($team) {
                return $team->teamMilestonestatus
                    ->filter(fn ($status) => ! is_null($status->graded_at))
                    ->sortByDesc('graded_at')
                    ->map(function ($status) use ($team) {
                        return [
                            'team_id' => $team->id,
                            'project_title' => $team->graduationProject?->proposal?->title,
                            'grade' => $status->milestone_grade,
                            'graded_at' => $status->graded_at?->format('Y-m-d H:i:s'),
                        ];
                    });
            })
            ->sortByDesc('graded_at')
            ->take(10)
            ->values();

        $recentActivity = ActivityLog::query()
            ->with(['team.graduationProject.proposal', 'user'])
            ->whereIn('team_id', $baseTeamIds)
            ->where('action', 'submission_uploaded')
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'message' => $activity->message,
                    'created_at' => $activity->created_at?->diffForHumans(),
                    'team_id' => $activity->team?->id,
                    'project_title' => $activity->team?->graduationProject?->proposal?->title,
                    'user' => [
                        'id' => $activity->user?->id,
                        'name' => $activity->user?->full_name,
                    ],
                ];
            })
            ->values();

        return response()->json([
            'message' => 'Team management data fetched successfully.',
            'data' => [
                'viewer_role' => $viewerRole,

                'filters' => [
                    'selected' => [
                        'search' => $search,
                        'department_id' => $departmentId,
                        'status' => $statusFilter,
                        'counterpart_id' => $counterpartId,
                    ],
                    'options' => [
                        'departments' => $departmentOptions,
                        'statuses' => $statusOptions,
                        $counterpartLabel => $counterpartOptions,
                    ],
                ],

                'statistics' => $statistics,
                'projects' => $projects->values(),
                'upcoming_meetings' => $upcomingMeetings,
                'pending_feedback' => $pendingFeedback,
                'recently_graded' => $recentlyGraded,
                'recent_activity' => $recentActivity,
            ]
        ]);
    }

    public function viewTeam(Request $request, $teamId, TeamDetailsService $teamDetailsService)
    {
        $user = $request->user();

        $team = Team::with([
            'department',
            'members.user',
            'graduationProject.proposal',
            'currentSupervisors',
            'teamMilestonestatus',
            'teamMilestonestatus.milestone',
            'submissions.files',
            'submissions.milestone',
            'submissions.submitter',
        ])
        ->activeYear()
        ->where('id', $teamId)
        ->whereHas('currentSupervisors', function ($q) use ($user) {
            $q->where('users.id', $user->id);
        })
        ->first();

        if (! $team) {
            return response()->json([
                'message' => 'Team not found or you are not authorized to view it'
            ], 404);
        }

        return response()->json(
            $teamDetailsService->buildResponse($team),
            200
        );
    }
}