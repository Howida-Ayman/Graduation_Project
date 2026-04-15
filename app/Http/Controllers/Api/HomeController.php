<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\DefenseCommittee;
use App\Models\PreviousProject;
use App\Models\SuggestedProject;
use App\Models\Department;
use App\Models\ProjectRule;
use App\Models\RuleItem;
use App\Models\TeamMembership;
use App\Models\Submission;
use App\Models\Milestone;
use App\Models\TeamSupervisor;
use App\Models\Proposal;
use App\Models\SubmissionFile;
use Illuminate\Http\Request;
use Carbon\Carbon;

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
                ->map(fn($project) => [
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
                    'suggested_projects_ideas' => SuggestedProject::where('is_active', true)->limit(4)->get(['id', 'title']),
                ]
            ]);
        }

        // ================== USER ==================

        $membership = TeamMembership::where('student_user_id', $user->id)
            ->where('academic_year_id', $academicYear?->id)
            ->where('status', 'active')
            ->first();

        $team = $membership?->team;
        $hasTeam = !!$team;
        $isLeader = $hasTeam && $team->leader_user_id == $user->id;

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
                'project_guidelines' => RuleItem::where('section', 'idea_selection_criteria')->pluck('rules'),
            ]
        ];

        if (!$hasTeam) {
            $response['data']['welcome_message'] = "Welcome, {$user->full_name}!";
            return response()->json($response);
        }

        // ================== PROJECT ==================

        $project = Proposal::where('team_id', $team->id)
            ->where('status', 'approved')
            ->latest()
            ->first();

        if ($project) {
            $response['data']['project'] = [
                'id' => $project->id,
                'title' => $project->title,
                'description' => $project->description,
            ];
        }

        // ================== NEXT DEADLINE ==================

        $nextMilestone = Milestone::availableForSubmission()
            ->where('deadline', '>=', now())
            ->orderBy('deadline')
            ->get()
            ->first(function ($milestone) use ($team) {
                return !Submission::where('team_id', $team->id)
                    ->where('milestone_id', $milestone->id)
                    ->exists();
            });

        if ($nextMilestone) {
            $daysLeft = now()->diffInDays($nextMilestone->deadline);

            $response['data']['next_deadline'] = [
                'title' => $nextMilestone->title,
                'days_left' => $daysLeft,
            ];
        } else {
            $response['data']['next_deadline'] = null;
        }

        // ================== LAST FEEDBACK ==================

        $lastFeedbackFile = SubmissionFile::whereHas('submission', fn($q) =>
                $q->where('team_id', $team->id)
            )
            ->whereNotNull('feedback')
            ->orderByDesc('graded_at')
            ->first();

        if ($lastFeedbackFile) {
            $response['data']['last_feedback'] = [
                'text' => $lastFeedbackFile->feedback,
                'graded_by' => $lastFeedbackFile->grader?->full_name,
                'graded_at' => $lastFeedbackFile->graded_at?->format('F d, Y'),
            ];
        } else {
            $response['data']['last_feedback'] = null;
        }

        // ================== DEFENSE ==================

        $defense = DefenseCommittee::with('members.member')
            ->where('team_id', $team->id)
            ->first();

        $notes = [];

        if ($defense) {
            $members = $defense->members->map(fn($m) =>
                $m->member->full_name . ' (' . ucfirst($m->member_role) . ')'
            )->implode(', ');

            $notes[] = "Defense Date: " . $defense->scheduled_at?->format('F d, Y \a\t h:i A');
            $notes[] = "Location: " . $defense->location;
            $notes[] = "Committee: " . $members;
        } else {
            $notes[] = "Defense not scheduled yet";
        }

        $response['data']['important_notes'] = $notes;

        // ================== SUPERVISORS ==================

        $response['data']['supervisors'] = TeamSupervisor::with('supervisor')
            ->where('team_id', $team->id)
            ->whereNull('ended_at')
            ->get()
            ->map(fn($s) => [
                'name' => $s->supervisor->full_name,
                'role' => $s->supervisor_role,
            ]);

        return response()->json($response);
    }
}