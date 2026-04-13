<?php

namespace App\Http\Controllers\Api\Submission;

use App\Http\Controllers\Controller;
use App\Models\Milestone;
use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Models\TeamMembership;
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
            ]);

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

            // ✅ تحديث team_milestone_status
            DB::table('team_milestone_status')->updateOrInsert(
                [
                    'team_id' => $membership->team_id,
                    'milestone_id' => $request->milestone_id,
                ],
                [
                    'status' => $teamStatus,
                    'updated_at' => now(),
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
}