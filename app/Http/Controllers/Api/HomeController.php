<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\DefenseCommittee;
use App\Models\Department;
use App\Models\Milestone;
use App\Models\MilestoneCommittee;
use App\Models\PreviousProject;
use App\Models\ProjectRule;
use App\Models\RuleItem;
use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Models\SuggestedProject;
use App\Models\TeamMembership;
use App\Models\TeamSupervisor;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $user = null;

        if ($request->bearerToken()) {
            try {
                $user = auth('sanctum')->user();
            } catch (\Exception $e) {
                $user = null;
            }
        }

        $academicYear = AcademicYear::where('is_active', 1)->first();

        // ================== GUEST ==================
        if (!$user) {
            $featuredProjects = PreviousProject::with(['proposal.department'])
                ->latest()
                ->limit(3)
                ->get()
                ->map(fn ($project) => [
                    'title' => $project->proposal?->title,
                    'department' => $project->proposal?->department?->name,
                    'year' => $project->created_at?->format('Y'),
                ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'user_status' => 'guest',
                    'featured_projects' => $featuredProjects,
                    'statistics' => [
                        'projects' => PreviousProject::count(),
                        'ideas' => SuggestedProject::where('is_active', true)->count(),
                        'departments' => Department::count(),
                    ],
                    'project_guidelines' => RuleItem::where('section', 'idea_selection_criteria')->pluck('rules'),
                    'suggested_projects_ideas' => SuggestedProject::where('is_active', true)
                        ->limit(4)
                        ->get(['id', 'title']),
                ]
            ], 200);
        }

        // ================== USER ==================
        $membership = TeamMembership::with('team.graduationProject.proposal')
            ->where('student_user_id', $user->id)
            ->where('academic_year_id', $academicYear?->id)
            ->where('status', 'active')
            ->first();

        $team = $membership?->team;
        $hasTeam = (bool) $team;
        $isLeader = $hasTeam && (int) $team->leader_user_id === (int) $user->id;

        $response = [
            'success' => true,
            'data' => [
                'user_status' => $hasTeam ? ($isLeader ? 'team_leader' : 'team_member') : 'no_team',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->full_name,
                    'has_team' => $hasTeam,
                    'is_leader' => $isLeader,
                ],
                'project_guidelines' => $this->projectGuidelines(),
            ]
        ];

        // ================== NO TEAM ==================
        if (!$hasTeam) {
            $response['data']['welcome_message'] = "Welcome, {$user->full_name}!";
            return response()->json($response, 200);
        }

        // ================== PROJECT ==================
        $project = $team->graduationProject;
        $proposal = $project?->proposal;

        $response['data']['project'] = [
            'id' => $project?->id,
            'proposal_id' => $proposal?->id,
            'title' => $proposal?->title,
            'description' => $proposal?->description,
            'category' => $proposal?->category,
            'image_url' => $project?->image_url ?? $proposal?->image_url,
            'file_url' => $proposal?->attachment_file,
        ];

        // ================== NEXT DEADLINE ==================
        $nextMilestone = Milestone::availableForSubmission()
            ->where('deadline', '>=', now())
            ->whereDoesntHave('submissions', function ($q) use ($team) {
                $q->where('team_id', $team->id);
            })
            ->orderBy('deadline')
            ->first();

        $response['data']['next_deadline'] = $nextMilestone ? [
            'milestone_id' => $nextMilestone->id,
            'title' => $nextMilestone->title,
            'deadline' => $nextMilestone->deadline,
            'days_left' => intval(now()->diffInDays($nextMilestone->deadline)),
        ] : null;

        // ================== LAST FILE FEEDBACK ==================
        $lastFeedbackFile = SubmissionFile::with([
                'feedbackBy',
                'submission.milestone',
            ])
            ->whereHas('submission', function ($q) use ($team) {
                $q->where('team_id', $team->id);
            })
            ->whereNotNull('feedback')
            ->orderByDesc('feedback_at')
            ->first();

        $response['data']['last_feedback'] = $lastFeedbackFile ? [
            'file_id' => $lastFeedbackFile->id,
            'file_name' => $lastFeedbackFile->original_name,
            'milestone' => [
                'id' => $lastFeedbackFile->submission?->milestone?->id,
                'title' => $lastFeedbackFile->submission?->milestone?->title,
            ],
            'text' => $lastFeedbackFile->feedback,
            'feedback_by' => [
                'id' => $lastFeedbackFile->feedbackBy?->id,
                'name' => $lastFeedbackFile->feedbackBy?->full_name,
            ],
            'feedback_at' => $lastFeedbackFile->feedback_at,
        ] : null;

// ================== IMPORTANT NOTES ==================
$notes = [];

// 1) آخر نوت من لجنة المايلستون
$lastMilestoneCommitteeNote = \App\Models\MilestoneCommitteeGrade::with([
        'milestone.projectCourse',
        'gradedBy',
    ])
    ->where('team_id', $team->id)
    ->whereNotNull('notes')
    ->latest('graded_at')
    ->first();

if ($lastMilestoneCommitteeNote) {
    $notes[] = [
        'type' => 'milestone_committee_note',
        'text' => $lastMilestoneCommitteeNote->notes,
        'milestone' => [
            'id' => $lastMilestoneCommitteeNote->milestone?->id,
            'title' => $lastMilestoneCommitteeNote->milestone?->title,
            'project_course' => [
                'id' => $lastMilestoneCommitteeNote->milestone?->projectCourse?->id,
                'name' => $lastMilestoneCommitteeNote->milestone?->projectCourse?->name,
                'order' => $lastMilestoneCommitteeNote->milestone?->projectCourse?->order,
            ],
        ],
        'noted_by' => [
            'id' => $lastMilestoneCommitteeNote->gradedBy?->id,
            'name' => $lastMilestoneCommitteeNote->gradedBy?->full_name,
        ],
        'noted_at' => $lastMilestoneCommitteeNote->graded_at,
    ];
}

// 2) مواعيد ومكان لجان المناقشة سواء Project I أو Project II
$defenseCommittees = DefenseCommittee::with([
        'projectCourse',
        'members.member',
    ])
    ->where('team_id', $team->id)
    ->orderBy('scheduled_at')
    ->get();

foreach ($defenseCommittees as $defense) {
    $notes[] = [
        'type' => 'defense_schedule',
        'text' => 'Defense discussion has been scheduled.',
        'project_course' => [
            'id' => $defense->projectCourse?->id,
            'name' => $defense->projectCourse?->name,
            'order' => $defense->projectCourse?->order,
        ],
        'date' => $defense->scheduled_at
            ? \Carbon\Carbon::parse($defense->scheduled_at)->format('Y-m-d')
            : null,
        'time' => $defense->scheduled_at
            ? \Carbon\Carbon::parse($defense->scheduled_at)->format('H:i')
            : null,
        'location' => $defense->location,
        'status' => $defense->status,
    ];
}

$response['data']['important_notes'] = $notes;

        // ================== SUPERVISORS ==================
        $response['data']['supervisors'] = TeamSupervisor::with('supervisor')
            ->where('team_id', $team->id)
            ->whereNull('ended_at')
            ->get()
            ->map(fn ($s) => [
                'id' => $s->supervisor?->id,
                'name' => $s->supervisor?->full_name,
                'role' => $s->supervisor_role,
                'image' => $s->supervisor?->profile_image_url,
            ])
            ->values();

        // ================== MILESTONE COMMITTEE ==================
        $committee = MilestoneCommittee::with('members.user')
            ->where('team_id', $team->id)
            ->first();

        $response['data']['milestone_committee'] = $committee ? [
            'id' => $committee->id,
            'members' => $committee->members->map(fn ($member) => [
                'id' => $member->user?->id,
                'name' => $member->user?->full_name,
                'role' => $member->member_role,
                'image' => $member->user?->profile_image_url,
            ])->values(),
        ] : null;

        return response()->json($response, 200);
    }

    private function projectGuidelines()
    {
        $rules = ProjectRule::first();

        $guidelines = RuleItem::where('section', 'idea_selection_criteria')
            ->pluck('rules')
            ->values();

        if ($rules) {
            $guidelines->push("Minimum team size is {$rules->min_team_size} and maximum is {$rules->max_team_size}.");
            $guidelines->push("Supervisor Evaluation: {$rules->supervisor_max_score} marks.");
            $guidelines->push("Final Discussion: {$rules->defense_max_score} marks.");
        }

        return $guidelines;
    }
}