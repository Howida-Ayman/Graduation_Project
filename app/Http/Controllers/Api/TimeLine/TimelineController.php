<?php

namespace App\Http\Controllers\Api\TimeLine;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Milestone;
use App\Models\TeamMembership;
use App\Models\DefenseCommittee;
use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Models\TeamMilestonStatus;
use Illuminate\Http\Request;

class TimelineController extends Controller
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

        // ✅ milestones مرتبطة بالسنة + flags
        $milestones = Milestone::availableForSubmission()
            ->orderBy('phase_number')
            ->get();

        $milestonesData = $milestones->map(function ($milestone) {
            return [
                'id' => $milestone->id,
                'title' => $milestone->title,
                'description' => $milestone->description,
                'start_date' => $milestone->start_date?->format('F d, Y'),
                'deadline' => $milestone->deadline?->format('F d, Y'),
                'status' => $milestone->status,
            ];
        });

        $response = [
            'success' => true,
            'data' => [
                'milestones' => $milestonesData,
            ]
        ];

        if ($user && $academicYear) {
            $membership = TeamMembership::where('student_user_id', $user->id)
                ->where('academic_year_id', $academicYear->id)
                ->where('status', 'active')
                ->first();

            $teamId = $membership?->team_id;

            if ($teamId) {
                $defenseCommittee = DefenseCommittee::with(['members.member'])
                    ->where('team_id', $teamId)
                    ->first();

                if ($defenseCommittee) {
                    $response['data']['defense_committee'] = [
                        'scheduled_at' => $defenseCommittee->scheduled_at?->format('F d, Y \a\t h:i A'),
                        'location' => $defenseCommittee->location,
                        'status' => $defenseCommittee->status,
                        'members' => $defenseCommittee->members->map(function ($member) {
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

    public function publicShow($id)
    {
        $milestone = Milestone::with('requirements')
            ->where('is_active', true)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $milestone->id,
                'title' => $milestone->title,
                'description' => $milestone->description,
                'start_date' => $milestone->start_date?->format('F d, Y'),
                'due_date' => $milestone->deadline?->format('F d, Y'),
                'requirements' => $milestone->requirements->map(fn($req) => [
                    'id' => $req->id,
                    'requirement' => $req->requirement,
                ]),
                'notes' => $milestone->notes,
            ]
        ]);
    }

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

            $academicYear = AcademicYear::where('is_active', 1)->first();

            $milestone = Milestone::with('requirements')->findOrFail($id);

            $membership = TeamMembership::where('student_user_id', $user->id)
                ->where('academic_year_id', $academicYear->id)
                ->where('status', 'active')
                ->first();

            $teamId = $membership?->team_id;

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

                // ✅ optimized feedback loading
                $submission = Submission::with('files')
                    ->where('milestone_id', $id)
                    ->where('team_id', $teamId)
                    ->first();

                if ($submission) {
                    $filesFeedback = $submission->files
                        ->whereNotNull('feedback')
                        ->pluck('feedback')
                        ->values();

                    $generalFeedback = [
                        'submitted_at' => $submission->submitted_at?->format('F d, Y'),
                        'notes' => $submission->notes,
                        'feedback' => $filesFeedback,
                    ];
                }
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
                    'feedback' => $generalFeedback,
                    'requirements' => $milestone->requirements->map(fn($req) => [
                        'id' => $req->id,
                        'requirement' => $req->requirement,
                    ]),
                    'notes' => $milestone->notes,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server error'
            ], 500);
        }
    }
}