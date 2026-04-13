<?php

namespace App\Http\Controllers\Api\Submission;

use App\Http\Controllers\Controller;
use App\Models\Milestone;
use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Models\TeamMembership;
use App\Models\TeamMilestonStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class SubmissionController extends Controller
{
    /**
     * جلب الميليستونز المتاحة للتسليم
     */
    public function getActiveMilestones(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $membership = TeamMembership::where('student_user_id', $user->id)
                ->where('status', 'active')
                ->first();

            if (!$membership) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not in any team'
                ], 403);
            }

            $milestones = DB::select("
                SELECT id, title, description, deadline
                FROM milestones
                WHERE status = 'on_progress' AND is_open = 1
                ORDER BY phase_number ASC
            ");

            $data = [];
            foreach ($milestones as $milestone) {
                $data[] = [
                    'id' => $milestone->id,
                    'title' => $milestone->title,
                    'description' => $milestone->description,
                    'deadline' => $milestone->deadline,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    /**
     * رفع تسليم جديد (Submission)
     */
    public function uploadSubmission(Request $request)
    {
        $user = $request->user();

        DB::beginTransaction();

        try {
            $membership = TeamMembership::where('student_user_id', $user->id)
                ->where('status', 'active')
                ->first();

            if (!$membership) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not in any team'
                ], 403);
            }

            $request->validate([
                'milestone_id' => 'required|exists:milestones,id',
                'notes' => 'nullable|string|max:1000',
                'files' => 'required|array|min:1',
                'files.*' => 'required|file|max:20480',
            ]);

            $milestone = Milestone::where('id', $request->milestone_id)
                ->where('status', 'on_progress')
                ->where('is_open', true)
                ->first();

            if (!$milestone) {
                return response()->json([
                    'success' => false,
                    'message' => 'This milestone is not available for submission'
                ], 400);
            }

            // حساب team_status
            $now = now();
            $deadline = Carbon::parse($milestone->deadline);
            $teamStatus = $now <= $deadline ? 'on_track' : 'delayed';

            // إنشاء الـ submission
            $submission = Submission::create([
                'milestone_id' => $request->milestone_id,
                'team_id' => $membership->team_id,
                'submitted_by_user_id' => $user->id,
                'notes' => $request->notes,
                'submitted_at' => $now,
                'team_status' => $teamStatus,
            ]);
            TeamMilestonStatus::updateOrCreate(
            [
           'team_id' => $membership->team_id,
           'milestone_id' => $milestone->id,
            ],
            [
            'status' => $teamStatus,
            ]
            );

            $uploadedFiles = [];

            foreach ($request->file('files') as $file) {
                $originalName = $file->getClientOriginalName();
                $path = $file->store('submissions/' . $membership->team_id . '/' . $submission->id, 'public');

                $submissionFile = SubmissionFile::create([
                    'submission_id' => $submission->id,
                    'file_url' => asset('storage/' . $path),
                    'original_name' => $originalName,
                    'uploaded_at' => now(),
                ]);

                $uploadedFiles[] = [
                    'id' => $submissionFile->id,
                    'file_name' => $originalName,
                    'file_url' => $submissionFile->file_url,
                ];
            }
            $projectTitle = $membership->team?->graduationProject?->proposal?->title ?? 'Unknown Project';
            // ✅ تسجيل Activity: رفع Submission
            log_activity(
           teamId: $membership->team_id,
           userId: $user->id,
           action: 'submission_uploaded',
           message: "Team \"$projectTitle\" uploaded a new submission in milestone \"$milestone->title\"",
           meta: [
          'submission_id' => $submission->id,
          'milestone_id' => $milestone->id,
          'files_count' => count($uploadedFiles),
          'team_status' => $teamStatus,
                 ]
                 );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Submission uploaded successfully',
                'data' => [
                    'submission_id' => $submission->id,
                    'milestone_id' => $milestone->id,
                    'team_status' => $teamStatus,
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Upload failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
  public function addFeedback(Request $request, $fileId)
{
    $request->validate([
        'feedback' => 'required|string|max:2000',
    ]);

    $user = $request->user();

    $file = SubmissionFile::findOrFail($fileId);

    $team = $file->submission->team;

    $isSupervisor = $team->supervisors()
        ->where('users.id', $user->id)
        ->exists();

    if (! $isSupervisor) {
        return response()->json([
            'message' => 'Unauthorized. You are not supervising this team.'
        ], 403);
    }

    $file->update([
        'feedback' => $request->feedback,
    ]);

    $projectTitle = $team?->graduationProject?->proposal?->title ?? 'Unknown Project';

    log_activity(
        teamId: $team->id,
        userId: $user->id,
        action: 'feedback_added',
        message: 'feedback was added on project "' . $projectTitle . '"',
        meta: [
            'submission_id' => $file->submission_id,
            'submission_file_id' => $file->id,
        ]
    );

    return response()->json([
    'message' => 'Feedback added successfully',
    'data' => [
        'id' => $file->id,
        'file_name' => $file->original_name,
        'feedback' => $file->feedback,
        'uploaded_at' => $file->uploaded_at->format('Y m d'),
    ]
]);
}
public function addGrade(Request $request, $teamId, $milestoneId)
{
    $request->validate([
        'grade' => 'required|decimal:2',
    ]);

    $user = $request->user();

    // نجيب الحالة
    $status = TeamMilestonStatus::with('team.graduationProject.proposal', 'milestone')
        ->where('team_id', $teamId)
        ->where('milestone_id', $milestoneId)
        ->firstOrFail();

    //  Authorization: لازم يكون مشرف
    $isSupervisor = $status->team
        ->supervisors()
        ->where('users.id', $user->id)
        ->exists();

    if (! $isSupervisor) {
        return response()->json([
            'message' => 'Unauthorized. You are not supervising this team.'
        ], 403);
    }

    //Update grade
    $status->update([
        'milestone_grade' => $request->grade,
        'graded_by_user_id' => $user->id,
        'graded_at' => now(),
    ]);

    
    $projectTitle = $status->team?->graduationProject?->proposal?->title ?? 'Unknown Project';

    // Activity Log
    log_activity(
        teamId: $teamId,
        userId: $user->id,
        action: 'grade_added',
        message: 'Team "' . $projectTitle . '" was graded with ' . $request->grade,
        meta: [
            'milestone_id' => $milestoneId,
            'grade' => $request->grade,
        ]
    );

    return response()->json([
        'message' => 'Grade added successfully',
        'data' => [
            'grade' => $status->milestone_grade,
            'milestone' => [
                'id' => $status->milestone->id,
                'title' => $status->milestone->title,
            ],
            'project_title' => $projectTitle,
        ]
    ]);
}
}