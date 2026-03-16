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

class SubmissionController extends Controller
{
    public function getActiveMilestones(Request $request)
    {
        $user = $request->user();

        $membership = TeamMembership::where('student_user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (!$membership) {
            return response()->json([
                'success' => false,
                'message' => 'You are not in any team'
            ], 403);
        }

        $milestones = Milestone::where('status', 'on_progress')
            ->where('is_open', true)
            ->orderBy('sort_order')
            ->get(['id', 'title', 'description', 'deadline']);

        return response()->json([
            'success' => true,
            'data' => $milestones->map(function($milestone) use ($membership) {
                $hasSubmission = Submission::where('milestone_id', $milestone->id)
                    ->where('team_id', $membership->team_id)
                    ->exists();

                return [
                    'id' => $milestone->id,
                    'title' => $milestone->title,
                    'description' => $milestone->description,
                    'deadline' => $milestone->deadline,
                    'can_submit' => !$hasSubmission,
                    'has_submission' => $hasSubmission,
                ];
            })
        ]);
    }

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

            $existing = Submission::where('milestone_id', $request->milestone_id)
                ->where('team_id', $membership->team_id)
                ->exists();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your team already submitted this milestone'
                ], 400);
            }

            $submission = Submission::create([
                'milestone_id' => $request->milestone_id,
                'team_id' => $membership->team_id,
                'submitted_by_user_id' => $user->id,
                'notes' => $request->notes,
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

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Submission uploaded successfully',
                'data' => [
                    'submission_id' => $submission->id,
                    'milestone_id' => $milestone->id,
                    'files_count' => count($uploadedFiles),
                    'files' => $uploadedFiles,
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