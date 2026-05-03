<?php

namespace App\Http\Controllers\Api\Submission;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\DatabaseNotification;
use App\Models\Milestone;
use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Models\Team;
use App\Models\TeamMembership;
use App\Models\TeamMilestonStatus;
use App\Models\TeamSupervisor;
use App\Models\User;
use App\Notifications\FeedbackAddedNotification;
use App\Notifications\GradeAddedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Str;

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
                'message' => 'User not authenticated.'
            ], 401);
        }

        $academicYear = AcademicYear::where('is_active', 1)->first();

        if (!$academicYear) {
            return response()->json([
                'success' => false,
                'message' => 'No active academic year found.'
            ], 404);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is not activated.'
            ], 403);
        }

        $activeEnrollment = $user->enrollments()
            ->where('academic_year_id', $academicYear->id)
            ->where('status', 'in_progress')
            ->whereHas('projectCourse', function ($q) {
                $q->whereIn('order', [1, 2]);
            })
            ->with('projectCourse')
            ->first();

        if (!$activeEnrollment) {
            return response()->json([
                'success' => false,
                'message' => 'You are not enrolled in an active capstone project.'
            ], 403);
        }

        $membership = TeamMembership::where('student_user_id', $user->id)
            ->where('academic_year_id', $academicYear->id)
            ->where('status', 'active')
            ->whereHas('team', function ($q) use ($academicYear) {
                $q->where('academic_year_id', $academicYear->id);
            })
            ->first();

        if (!$membership) {
            return response()->json([
                'success' => false,
                'message' => 'You are not in any team.'
            ], 403);
        }

        $milestones = Milestone::availableForSubmission()
            ->where('project_course_id', $activeEnrollment->project_course_id)
            ->orderBy('phase_number', 'asc')
            ->get([
                'id',
                'project_course_id',
                'title',
                'description',
                'deadline',
                'max_score',
                'status',
                'is_open'
            ]);

        $data = $milestones->map(function ($milestone) use ($membership) {
            $latestSubmission = Submission::where('team_id', $membership->team_id)
                ->where('milestone_id', $milestone->id)
                ->latest()
                ->first();

            return [
                'id' => $milestone->id,
                'project_course_id' => $milestone->project_course_id,
                'title' => $milestone->title,
                'description' => $milestone->description,
                'deadline' => $milestone->deadline,
                'max_score' => $milestone->max_score,
                'status' => $milestone->status,
                'is_open' => $milestone->is_open,
                'has_submission' => (bool) $latestSubmission,
                'latest_submission_id' => $latestSubmission?->id,
                'latest_submitted_at' => $latestSubmission?->submitted_at,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'project_course' => [
                'id' => $activeEnrollment->projectCourse?->id,
                'name' => $activeEnrollment->projectCourse?->name,
                'order' => $activeEnrollment->projectCourse?->order,
            ],
            'data' => $data
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve available milestones.',
            'error' => config('app.debug') ? $e->getMessage() : null,
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
        $academicYear = AcademicYear::where('is_active', 1)->first();

        if (!$academicYear) {
            return response()->json([
                'success' => false,
                'message' => 'No active academic year found.'
            ], 404);
        }

        if (!$user || !$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is not activated.'
            ], 403);
        }

        $activeEnrollment = $user->enrollments()
            ->where('academic_year_id', $academicYear->id)
            ->where('status', 'in_progress')
            ->whereHas('projectCourse', function ($q) {
                $q->whereIn('order', [1, 2]);
            })
            ->with('projectCourse')
            ->first();

        if (!$activeEnrollment) {
            return response()->json([
                'success' => false,
                'message' => 'You are not allowed to upload submissions because you are not enrolled in an active capstone project.'
            ], 403);
        }

        $membership = TeamMembership::where('student_user_id', $user->id)
            ->where('academic_year_id', $academicYear->id)
            ->where('status', 'active')
            ->whereHas('team', function ($q) use ($academicYear) {
                $q->where('academic_year_id', $academicYear->id);
            })
            ->first();

        if (!$membership) {
            return response()->json([
                'success' => false,
                'message' => 'You are not in any team.'
            ], 403);
        }

        $request->validate([
            'milestone_id' => 'required|exists:milestones,id',
            'notes' => 'nullable|string|max:1000',
            'files' => 'required|array|min:1',
            'files.*' => 'required|file|max:20480',
        ]);

        $milestone = Milestone::availableForSubmission()
            ->where('id', $request->milestone_id)
            ->where('project_course_id', $activeEnrollment->project_course_id)
            ->first();

        if (!$milestone) {
            return response()->json([
                'success' => false,
                'message' => 'This milestone is not available for submission for your current capstone project.'
            ], 400);
        }

        $now = now();
        $deadline = Carbon::parse($milestone->deadline);
        $teamStatus = $now <= $deadline ? 'on_track' : 'delayed';

        $submission = Submission::create([
            'milestone_id' => $milestone->id,
            'team_id' => $membership->team_id,
            'submitted_by_user_id' => $user->id,
            'notes' => $request->notes,
            'submitted_at' => $now,
        ]);

        TeamMilestonStatus::updateOrCreate(
            [
                'team_id' => $membership->team_id,
                'milestone_id' => $milestone->id,
            ],
            [
                'status' => $teamStatus,
                'status_updated_at' => now(),
            ]
        );

        $uploadedFiles = [];

        foreach ($request->file('files') as $file) {
            $originalName = $file->getClientOriginalName();

            $path = $file->store(
                'submissions/' . $membership->team_id . '/' . $submission->id,
                'public'
            );

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

        log_activity(
            teamId: $membership->team_id,
            userId: $user->id,
            action: 'submission_uploaded',
            message: "Team \"$projectTitle\" uploaded a new submission in milestone \"$milestone->title\"",
            meta: [
                'submission_id' => $submission->id,
                'milestone_id' => $milestone->id,
                'project_course_id' => $milestone->project_course_id,
                'files_count' => count($uploadedFiles),
                'team_status' => $teamStatus,
            ]
        );

        $team = Team::find($membership->team_id);

        $supervisors = TeamSupervisor::where('team_id', $membership->team_id)
            ->whereNull('ended_at')
            ->with('supervisor')
            ->get();

        foreach ($supervisors as $supervisor) {
            if ($supervisor->supervisor) {
                DatabaseNotification::create([
                    'id' => (string) Str::uuid(),
                    'type' => 'submission_uploaded',
                    'notifiable_type' => 'App\\Models\\User',
                    'notifiable_id' => $supervisor->supervisor->id,
                    'academic_year_id' => $academicYear->id,
                    'data' => [
                        'type' => 'submission_uploaded',
                        'submission_id' => $submission->id,
                        'milestone_id' => $milestone->id,
                        'milestone_title' => $milestone->title,
                        'project_course_id' => $milestone->project_course_id,
                        'team_id' => $membership->team_id,
                        'team_name' => $team?->name ?? "Team {$membership->team_id}",
                        'message' => "Team '" . ($team?->name ?? "Team {$membership->team_id}") . "' has uploaded a submission for milestone '{$milestone->title}'",
                        'icon' => 'upload',
                        'color' => 'green',
                        'created_at' => now(),
                    ],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Submission uploaded successfully.',
            'data' => [
                'submission_id' => $submission->id,
                'milestone_id' => $milestone->id,
                'project_course_id' => $milestone->project_course_id,
                'team_status' => $teamStatus,
                'files' => $uploadedFiles,
            ]
        ], 201);

    } catch (\Illuminate\Validation\ValidationException $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'errors' => $e->errors(),
        ], 422);

    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'Upload failed.',
            'error' => config('app.debug') ? $e->getMessage() : null,
        ], 500);
    }
}

    public function addFeedback(Request $request, $fileId)
    {
        $request->validate([
            'feedback' => 'required|string|max:2000',
        ]);

        $user = $request->user();

        $academicYear = AcademicYear::where('is_active', 1)->first();

        if (!$academicYear) {
            return response()->json([
                'message' => 'No active academic year found'
            ], 404);
        }

        $file = SubmissionFile::with('submission.team')->findOrFail($fileId);

        $team = $file->submission->team;

        if (!$team || (int) $team->academic_year_id !== (int) $academicYear->id) {
            return response()->json([
                'message' => 'This submission does not belong to the active academic year.'
            ], 403);
        }

        $isSupervisor = $team->supervisors()
            ->where('users.id', $user->id)
            ->where('users.is_active', 1)
            ->exists();

        if (!$isSupervisor) {
            return response()->json([
                'message' => 'Unauthorized. You are not supervising this team.'
            ], 403);
        }

        $file->update([
            'feedback' => $request->feedback,
        ]);
        // بعد update الـ file
$academicYear = AcademicYear::where('is_active', true)->first();

$team = $file->submission->team;
$members = TeamMembership::where('team_id', $team->id)
    ->where('status', 'active')
    ->with('user')
    ->get();

foreach ($members as $member) {
    if ($member->user) {
        DatabaseNotification::create([
            'id' => (string) Str::uuid(),
            'type' => 'feedback_added',
            'notifiable_type' => User::class,
            'notifiable_id' => $member->user->id,
            'academic_year_id' => $academicYear->id,
            'data' => [
                'type' => 'feedback_added',
                'file_name' => $file->original_name,
                'feedback' => $request->feedback,
                'message' => "New feedback on your file '{$file->original_name}'",
                'icon' => 'message-circle',
                'color' => 'purple',
                'created_at' => now(),
            ],
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

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
                'uploaded_at' => $file->uploaded_at?->format('Y m d'),
            ]
        ]);
    }

    public function addGrade(Request $request, $teamId, $milestoneId)
    {
        $request->validate([
            'grade' => 'required|numeric',
        ]);

        $user = $request->user();

        $academicYear = AcademicYear::where('is_active', 1)->first();

        if (!$academicYear) {
            return response()->json([
                'message' => 'No active academic year found'
            ], 404);
        }

        $status = TeamMilestonStatus::with('team.graduationProject.proposal', 'milestone')
            ->where('team_id', $teamId)
            ->where('milestone_id', $milestoneId)
            ->whereHas('team', function ($q) use ($academicYear) {
                $q->where('academic_year_id', $academicYear->id);
            })
            ->firstOrFail();

        $isSupervisor = $status->team
            ->supervisors()
            ->where('users.id', $user->id)
            ->where('users.is_active', 1)
            ->exists();

        if (!$isSupervisor) {
            return response()->json([
                'message' => 'Unauthorized. You are not supervising this team.'
            ], 403);
        }

        $status->update([
            'milestone_grade' => $request->grade,
            'graded_by_user_id' => $user->id,
            'graded_at' => now(),
        ]);
        // بعد update الـ status
$academicYear = AcademicYear::where('is_active', true)->first();

$team = Team::find($teamId);
$members = TeamMembership::where('team_id', $team->id)
    ->where('status', 'active')
    ->with('user')
    ->get();

foreach ($members as $member) {
    if ($member->user) {
        DatabaseNotification::create([
            'id' => (string) Str::uuid(),
            'type' => 'grade_added',
            'notifiable_type' => User::class,
            'notifiable_id' => $member->user->id,
            'academic_year_id' => $academicYear->id,
            'data' => [
                'type' => 'grade_added',
                'milestone_id' => $milestoneId,
                'milestone_title' => $status->milestone->title,
                'grade' => $request->grade,
                'message' => "Your team received {$request->grade}% for milestone '{$status->milestone->title}'",
                'icon' => 'star',
                'color' => 'yellow',
                'created_at' => now(),
            ],
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

        $projectTitle = $status->team?->graduationProject?->proposal?->title ?? 'Unknown Project';

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

    public function deleteGrade(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'team_id' => 'required|exists:teams,id',
            'milestone_id' => 'required|exists:milestones,id',
        ]);

        $academicYear = AcademicYear::where('is_active', 1)->first();

        if (!$academicYear) {
            return response()->json([
                'message' => 'No active academic year found'
            ], 404);
        }

        $isAuthorized = Team::where('id', $validated['team_id'])
            ->where('academic_year_id', $academicYear->id)
            ->whereHas('currentSupervisors', function ($q) use ($user) {
                $q->where('users.id', $user->id)
                  ->where('users.is_active', 1)
                  ->where('team_supervisors.supervisor_role', 'doctor');
            })
            ->exists();

        if (!$isAuthorized) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $row = TeamMilestonStatus::where('team_id', $validated['team_id'])
            ->where('milestone_id', $validated['milestone_id'])
            ->whereHas('team', function ($q) use ($academicYear) {
                $q->where('academic_year_id', $academicYear->id);
            })
            ->first();

        if (!$row) {
            return response()->json([
                'message' => 'Milestone record not found'
            ], 404);
        }

        $row->update([
            'milestone_grade' => null,
            'graded_by_user_id' => null,
            'graded_at' => null,
        ]);

        return response()->json([
            'message' => 'Grade deleted successfully'
        ], 200);
    }
}