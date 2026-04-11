<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
use Illuminate\Support\Facades\DB;
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
        // $user = $request->user();
        
        // ========== البيانات الأساسية ==========
        $projectRules = ProjectRule::first();
        
        // ========== Guest (غير مسجل) ==========
        if (!$user) {
            $featuredProjects = PreviousProject::with(['proposal.department'])
                ->latest()
                ->limit(3)
                ->get()
                ->map(function($project) {
                    return [
                        'title' => $project->proposal?->title,
                        'department' => $project->proposal?->department?->name,
                        'year' => $project->created_at?->format('Y'),
                    ];
                });
            
            $statistics = [
                'projects' => PreviousProject::count(),
                'ideas' => SuggestedProject::where('is_active', true)->count(),
                'departments' => Department::count(),
            ];
            
            $ideaSelectionCriteria = RuleItem::where('section', 'idea_selection_criteria')
                ->pluck('rules');
            
            $suggestedIdeas = SuggestedProject::where('is_active', true)
                ->limit(4)
                ->get(['id', 'title']);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'user_status' => 'guest',
                    'featured_projects' => $featuredProjects,
                    'statistics' => $statistics,
                    'project_guidelines' => $ideaSelectionCriteria,
                    'suggested_projects_ideas' => $suggestedIdeas,
                ]
            ]);
        }
        
        // ========== البيانات المشتركة للمستخدم المسجل ==========
        
        $ideaSelectionCriteria = RuleItem::where('section', 'idea_selection_criteria')
            ->pluck('rules');
        
        $response = [
            'success' => true,
            'data' => [
                'project_guidelines' => $ideaSelectionCriteria,
            ]
        ];
        
        // جلب حالة الفريق
        $membership = TeamMembership::where('student_user_id', $user->id)
            ->where('status', 'active')
            ->first();
        
        $hasTeam = !is_null($membership);
        $team = $hasTeam ? $membership->team : null;
        $isLeader = $hasTeam && $team->leader_user_id == $user->id;
        
        $response['data']['user'] = [
            'id' => $user->id,
            'name' => $user->full_name,
            'has_team' => $hasTeam,
            'is_leader' => $isLeader,
        ];
        
        // ========== مش في فريق ==========
        if (!$hasTeam) {
            $response['data']['user_status'] = 'no_team';
            $response['data']['name'] = $user->full_name;
            $response['data']['welcome_message'] = "Welcome, {$user->full_name}!";
            
            return response()->json($response);
        }
        
        // ========== في فريق ==========

        // ✅ جلب الـ Project الخاص بالفريق
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

        // ✅ جلب الـ next deadline (أقرب milestone لسه مجاش)
        $nextMilestone = Milestone::where('deadline', '>=', now())
            ->orderBy('deadline', 'asc')
            ->first();

        $nextDeadline = null;
        $nextDeadlineDays = null;

        if ($nextMilestone) {
            $submission = Submission::where('milestone_id', $nextMilestone->id)
                ->where('team_id', $team->id)
                ->first();
            
            if (!$submission) {
                $deadline = Carbon::parse($nextMilestone->deadline);
                $today = now();
                $nextDeadline = $nextMilestone->title;
                $nextDeadlineDays = (int) $today->diffInDays($deadline);
            }
        }

            $lastFeedbackFile = SubmissionFile::whereHas('submission', function($q) use ($team) {
                $q->where('team_id', $team->id);
            })
            ->whereNotNull('feedback')
            ->latest('graded_at')
            ->first();

        $lastFeedback = null;
        if ($lastFeedbackFile) {
            $lastFeedback = [
                'text' => $lastFeedbackFile->feedback,
                'graded_by' => $lastFeedbackFile->grader?->full_name,
                'graded_at' => $lastFeedbackFile->graded_at?->format('F d, Y'),
            ];
        }

        $response['data']['last_feedback'] = $lastFeedback;


        $defenseCommittee = DefenseCommittee::with(['members.member'])
    ->where('team_id', $team->id)
    ->first();

$importantNotes = [];

if ($defenseCommittee) {
    $membersList = $defenseCommittee->members->map(function($member) {
        $role = $member->member_role == 'doctor' ? 'Doctor' : 'TA';
        return $member->member->full_name . ' (' . $role . ')';
    })->implode(', ');
    
    $importantNotes[] = "Defense Date: " . ($defenseCommittee->scheduled_at?->format('F d, Y \a\t h:i A'));
    $importantNotes[] = "Location: " . $defenseCommittee->location;
    $importantNotes[] = "Committee Members: " . $membersList;
} else {
    // لو مفيش لجنة مناقشة، رسالة افتراضية
    $importantNotes[] = "Defense committee not scheduled yet";
    $importantNotes[] = "Date and location will be announced later";
}

$response['data']['important_notes'] = $importantNotes;

$response['data']['important_notes'] = $importantNotes;
        // ✅ جلب المشرفين
        $supervisors = TeamSupervisor::with('supervisor')
            ->where('team_id', $team->id)
            ->whereNull('ended_at')
            ->get()
            ->map(function($s) {
                return [
                    'name' => $s->supervisor->full_name,
                    'role' => $s->supervisor_role,
                ];
            });

        $response['data']['user_status'] = $isLeader ? 'team_leader' : 'team_member';
        $response['data']['next_deadline'] = $nextDeadline ? ['days_left' => $nextDeadlineDays] : null;
        $response['data']['last_feedback'] = $lastFeedback;
        $response['data']['important_notes'] = $importantNotes;
        $response['data']['supervisors'] = $supervisors;
        
        return response()->json($response);
    }
}