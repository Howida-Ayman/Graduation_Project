<?php

namespace App\Http\Controllers\Api\TimeLine;

use App\Http\Controllers\Controller;
use App\Models\Milestone;
use App\Models\TeamMembership;
use App\Models\DefenseCommittee;
use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Models\TeamMilestonStatus;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TimelineController extends Controller
{
    /**
     * عرض الـ Timeline للجميع (Guest + مسجل)
     * - Guest: milestones بس
     * - مسجل (في فريق): milestones + defense_committee
     */
    public function index(Request $request)
    {
        // ✅ محاولة جلب المستخدم من التوكن بدون middleware إجباري
        $user = null;
        if ($request->bearerToken()) {
            try {
                $user = auth('sanctum')->user();
            } catch (\Exception $e) {
                $user = null;
            }
        }
        
        // 1. جلب الميليستونز للكل
        $milestones = Milestone::orderBy('phase_number')->get();
        
        $milestonesData = [];
        foreach ($milestones as $milestone) {
            $milestonesData[] = [
                'id' => $milestone->id,
                'title' => $milestone->title,
                'description' => $milestone->description,
                'start_date' => $milestone->start_date?->format('F d, Y'),
                'deadline' => $milestone->deadline?->format('F d, Y'),
                'status' => $milestone->status,
            ];
        }
        
        $response = [
            'success' => true,
            'data' => [
                'milestones' => $milestonesData,
            ]
        ];
        
        // 2. لو فيه مستخدم مسجل، نجيب الـ team_id
        if ($user) {
            $membership = TeamMembership::where('student_user_id', $user->id)
                ->where('status', 'active')
                ->first();
            $teamId = $membership?->team_id;
            
            // 3. لو في فريق، نجيب بيانات المناقشة
            if ($teamId) {
                $defenseCommittee = DefenseCommittee::with(['members.member'])
                    ->where('team_id', $teamId)
                    ->first();
                
                if ($defenseCommittee) {
                    $response['data']['defense_committee'] = [
                        'scheduled_at' => $defenseCommittee->scheduled_at?->format('F d, Y \a\t h:i A'),
                        'location' => $defenseCommittee->location,
                        'status' => $defenseCommittee->status,
                        'members' => $defenseCommittee->members->map(function($member) {
                            return [
                                'name' => $member->member?->full_name,
                                'role' => $member->member_role,
                            ];
                        }),
                    ];
                }
            }
        }
        
        return response()->json($response);
    }
    
    /**
     * عرض تفاصيل المهمة (Task Details) للجميع (بدون grade و feedback)
     */
    public function publicShow($id)
    {
        $milestone = Milestone::with('requirements')->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $milestone->id,
                'title' => $milestone->title,
                'description' => $milestone->description,
                'start_date' => $milestone->start_date?->format('F d, Y'),
                'due_date' => $milestone->deadline?->format('F d, Y'),
                'requirements' => $milestone->requirements->map(function($req) {
                    return [
                        'id' => $req->id,
                        'requirement' => $req->requirement,
                    ];
                }),
                'notes' => $milestone->notes,
            ]
        ]);
    }
    
    /**
     * عرض تفاصيل المهمة (Task Details) للمسجلين فقط
     * - الجريد من team_milestone_status
     * - الفيدباك من submission_files
     */
public function show(Request $request, $id)
{
    try {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }
        
        $milestone = Milestone::with('requirements')->findOrFail($id);
        
        $membership = TeamMembership::where('student_user_id', $user->id)
            ->where('status', 'active')
            ->first();
        $teamId = $membership?->team_id;
        
        // ✅ جلب الجريد والفيدباك العام للميلستون
        $milestoneGrade = null;
        $gradedAt = null;
        $generalFeedback = null;
        
        if ($teamId) {
            $teamMilestoneStatus = TeamMilestonStatus::where('team_id', $teamId)
                ->where('milestone_id', $id)
                ->first();
            
            if ($teamMilestoneStatus) {
                $milestoneGrade = $teamMilestoneStatus->milestone_grade;
                $gradedAt = $teamMilestoneStatus->graded_at?->format('F d, Y');
            }
            
            // ✅ جلب الفيدباك العام من submission_files (مرة واحدة)
            $submission = Submission::where('milestone_id', $id)
                ->where('team_id', $teamId)
                ->first();
            
            if ($submission) {
                $submissionFiles = SubmissionFile::where('submission_id', $submission->id)
                    ->whereNotNull('feedback')
                    ->get();
                
                $filesFeedback = [];
                foreach ($submissionFiles as $file) {
                    if ($file->feedback) {
                        $filesFeedback[] = $file->feedback; // ✅ نص الفيدباك بس
                    }
                }
                
                $generalFeedback = [
                    'submitted_at' => $submission->submitted_at?->format('F d, Y'),
                    'notes' => $submission->notes,
                    'feedback' => $filesFeedback,
                ];
            }
        }
        
        // ✅ الـ requirements من غير تكرار الفيدباك
        $requirements = [];
        foreach ($milestone->requirements as $req) {
            $requirements[] = [
                'id' => $req->id,
                'requirement' => $req->requirement,
            ];
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $milestone->id,
                'title' => $milestone->title,
                'description' => $milestone->description,
                'start_date' => $milestone->start_date?->format('F d, Y'),
                'due_date' => $milestone->deadline?->format('F d, Y'),
                'milestone_grade' => $milestoneGrade,
                'graded_at' => $gradedAt,
                'feedback' => $generalFeedback, // ✅ فيدباك واحد
                'requirements' => $requirements,
                'notes' => $milestone->notes,
            ]
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Server error',
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile()
        ], 500);
    }
}
}