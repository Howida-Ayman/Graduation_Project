<?php

namespace App\Http\Controllers\Api\Submission;

use App\Http\Controllers\Controller;
use App\Models\Milestone;
use App\Models\MilestoneRequirement;
use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Models\TeamMembership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SubmissionController extends Controller
{
    /**
     * جلب الـ requirements المتاحة للفريق
     */
    public function getTeamRequirements(Request $request)
    {
        $user = $request->user();

        // 1. نجيب الفريق بتاع المستخدم
        $membership = TeamMembership::where('student_user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (!$membership) {
            return response()->json([
                'success' => false,
                'message' => 'You are not in any team'
            ], 403);
        }

        // 2. نجيب الـ milestones المفتوحة مع الـ requirements
        $milestones = Milestone::with(['requirements' => function($q) use ($membership) {
            $q->withExists(['submissions as has_submission' => function($sub) use ($membership) {
                $sub->where('team_id', $membership->team_id);
            }]);
        }])
        ->where('is_open', true)
        ->orderBy('sort_order')
        ->get();

        // 3. تجهيز البيانات للـ Frontend
        return response()->json([
            'success' => true,
            'data' => $milestones->map(function($milestone) {
                return [
                    'id' => $milestone->id,
                    'title' => $milestone->title,
                    'deadline' => $milestone->deadline,
                    'status' => $milestone->status,
                    'requirements' => $milestone->requirements->map(function($req) {
                        return [
                            'id' => $req->id,
                            'requirement' => $req->requirement,
                            'can_submit' => !$req->has_submission, // لو مسلمش قبل كده
                        ];
                    }),
                ];
            })
        ]);
    }

    /**
     * رفع submission جديد
     */
    public function upload(Request $request)
    {
        $user = $request->user();

        DB::beginTransaction();

        try {
            // 1. نجيب الفريق
            $membership = TeamMembership::where('student_user_id', $user->id)
                ->where('status', 'active')
                ->first();

            if (!$membership) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not in any team'
                ], 403);
            }

            // 2. التحقق من البيانات
            $request->validate([
                'milestone_requirement_id' => 'required|exists:milestone_requirements,id',
                'notes' => 'nullable|string|max:1000',
                'files' => 'required|array|min:1',
                'files.*' => 'required|file|max:20480', // 20MB
            ]);

            // 3. التأكد إن الـ requirement موجود ومفتوح
            $requirement = MilestoneRequirement::with('milestone')
                ->where('id', $request->milestone_requirement_id)
                ->whereHas('milestone', function($q) {
                    $q->where('is_open', true);
                })
                ->first();

            if (!$requirement) {
                return response()->json([
                    'success' => false,
                    'message' => 'This requirement is not available for submission'
                ], 400);
            }

            // 4. التأكد إن الفريق مقدمش submission قبل كده
            $existing = Submission::where('milestone_requirement_id', $request->milestone_requirement_id)
                ->where('team_id', $membership->team_id)
                ->exists();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your team already submitted this requirement'
                ], 400);
            }

            // 5. إنشاء الـ submission
            $submission = Submission::create([
                'milestone_requirement_id' => $request->milestone_requirement_id,
                'team_id' => $membership->team_id,
                'submitted_by_user_id' => $user->id,
                'notes' => $request->notes,
            ]);

            // 6. رفع الملفات
            $uploadedFiles = [];
            foreach ($request->file('files') as $file) {
                $originalName = $file->getClientOriginalName();
                $path = $file->store('submissions/' . $membership->team_id, 'public');

                $submissionFile = SubmissionFile::create([
                    'submission_id' => $submission->id,
                    'file_url' => Storage::url($path),
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
                'message' => 'Task uploaded successfully',
                'data' => [
                    'submission_id' => $submission->id,
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