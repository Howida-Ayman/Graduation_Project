<?php

namespace App\Http\Controllers\Api\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Milestone;
use App\Models\Submission;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MilestoneManagementController extends Controller
{
    /**
     * Dashboard tabs + milestone cards
     *
     * query params:
     * - tab = all | on_progress | completed | pending | overdue
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $selectedTab = $request->query('tab', 'all');
        $now = now();

        $allowedTabs = ['all', 'on_progress', 'completed', 'pending', 'overdue'];
        if (!in_array($selectedTab, $allowedTabs)) {
            return response()->json([
                'message' => 'Invalid tab value'
            ], 422);
        }

        $activeYear = AcademicYear::where('is_active', true)->first();

        if (!$activeYear) {
            return response()->json([
                'message' => 'No active academic year found'
            ], 404);
        }

        $viewerRole = match ((int) $user->role_id) {
            2 => 'doctor',
            3 => 'ta',
            default => 'unknown',
        };

        // التيمات اللي المشرف الحالي مشرف عليها في السنة الفعالة فقط
        $teams = Team::query()
            ->where('academic_year_id', $activeYear->id)
            ->whereHas('currentSupervisors', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            })
            ->with([
                'graduationProject.proposal',
                'department',
            ])
            ->get();

        $teamIds = $teams->pluck('id')->values();

        // كل المايلستونز global
        $milestones = Milestone::query()
            ->orderBy('phase_number')
            ->get();

        $milestoneIds = $milestones->pluck('id')->values();

        // latest submission فقط لكل team + milestone
        $latestSubmissions = Submission::query()
            ->whereIn('team_id', $teamIds)
            ->whereIn('milestone_id', $milestoneIds)
            ->with('files')
            ->get()
            ->sortByDesc(function ($submission) {
                return $submission->submitted_at ?? $submission->created_at;
            })
            ->groupBy(function ($submission) {
                return $submission->milestone_id . '_' . $submission->team_id;
            })
            ->map(function ($group) {
                return $group->first();
            });

        $cards = $milestones->map(function ($milestone) use ($teams, $latestSubmissions, $now) {
            $teamsTotal = $teams->count();
            $submittedCount = 0;
            $lateTeamsCount = 0;

            foreach ($teams as $team) {
                $key = $milestone->id . '_' . $team->id;
                $submission = $latestSubmissions->get($key);

                if ($submission) {
                    $submittedCount++;
                } else {
                    if ($milestone->deadline && $now->gt(Carbon::parse($milestone->deadline))) {
                        $lateTeamsCount++;
                    }
                }
            }

            $hasOverdue = $lateTeamsCount > 0;

            return [
                'id' => $milestone->id,
                'title' => $milestone->title,
                'description' => $milestone->description,
                'phase_number' => $milestone->phase_number,
                'status' => $milestone->status, // الحالة العامة من الجدول
                'start_date' => optional($milestone->start_date)?->format('Y-m-d'),
                'deadline' => optional($milestone->deadline)?->format('Y-m-d'),
                'teams_total' => $teamsTotal,
                'teams_submitted' => $submittedCount,
                'teams_not_submitted' => max($teamsTotal - $submittedCount, 0),
                'late_teams_count' => $lateTeamsCount,
                'has_overdue' => $hasOverdue,
            ];
        })->values();

        // tab counts
        $tabs = [
            'all' => $cards->count(),
            'on_progress' => $cards->where('status', 'on_progress')->count(),
            'completed' => $cards->where('status', 'completed')->count(),
            'pending' => $cards->where('status', 'pending')->count(),
            'overdue' => $cards->where('has_overdue', true)->count(),
        ];

        // filter cards by selected tab
        $filteredCards = match ($selectedTab) {
            'on_progress' => $cards->where('status', 'on_progress')->values(),
            'completed' => $cards->where('status', 'completed')->values(),
            'pending' => $cards->where('status', 'pending')->values(),
            'overdue' => $cards->where('has_overdue', true)->values(),
            default => $cards->values(),
        };

        return response()->json([
            'message' => 'Milestones retrieved successfully',
            'data' => [
                'viewer_role' => $viewerRole,
                'academic_year' => [
                    'id' => $activeYear->id,
                    'code' => $activeYear->code,
                ],
                'selected_tab' => $selectedTab,
                'tabs' => $tabs,
                'milestones' => $filteredCards,
            ]
        ], 200);
    }

    /**
     * Teams inside one milestone
     *
     * query params:
     * - type = all | submitted | late | not_yet
     */
    public function viewTeams(Request $request, $milestoneId)
    {
        $user = $request->user();
        $type = $request->query('type', 'all');
        $now = now();

        $allowedTypes = ['all', 'submitted', 'late', 'not_yet'];
        if (!in_array($type, $allowedTypes)) {
            return response()->json([
                'message' => 'Invalid type value'
            ], 422);
        }

        $activeYear = AcademicYear::where('is_active', true)->first();

        if (!$activeYear) {
            return response()->json([
                'message' => 'No active academic year found'
            ], 404);
        }

        $milestone = Milestone::findOrFail($milestoneId);

        // التيمات اللي المشرف الحالي مشرف عليها في السنة الفعالة فقط
        $teams = Team::query()
            ->where('academic_year_id', $activeYear->id)
            ->whereHas('currentSupervisors', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            })
            ->with([
                'graduationProject.proposal',
                'department',
            ])
            ->get();

        $teamIds = $teams->pluck('id')->values();

        // latest submission فقط لنفس milestone
        $latestSubmissions = Submission::query()
            ->whereIn('team_id', $teamIds)
            ->where('milestone_id', $milestone->id)
            ->with('files')
            ->get()
            ->sortByDesc(function ($submission) {
                return $submission->submitted_at ?? $submission->created_at;
            })
            ->groupBy('team_id')
            ->map(function ($group) {
                return $group->first();
            });

        $teamsData = $teams->map(function ($team) use ($milestone, $latestSubmissions, $now) {
            $submission = $latestSubmissions->get($team->id);

            if ($submission) {
                $teamStatus = 'submitted';
            } else {
                $teamStatus = ($milestone->deadline && $now->gt(Carbon::parse($milestone->deadline)))
                    ? 'late'
                    : 'not_yet';
            }

            return [
                'team_id' => $team->id,
                'project_title' => $team->graduationProject?->proposal?->title,
                'department' => [
                    'id' => $team->department?->id,
                    'name' => $team->department?->name,
                ],
                'status' => $teamStatus,
                'submitted_at' => $submission?->submitted_at?->format('Y-m-d H:i:s'),
                'submission_id' => $submission?->id,
                'files' => $submission
                    ? $submission->files->map(function ($file) {
                        return [
                            'id' => $file->id,
                            'file_name' => $file->original_name,
                            'file_url' => $file->file_url,
                            'uploaded_at' => $file->uploaded_at?->format('Y-m-d H:i:s'),
                            'feedback' => $file->feedback,
                        ];
                    })->values()
                    : [],
            ];
        })->values();

        $filteredTeams = match ($type) {
            'submitted' => $teamsData->where('status', 'submitted')->values(),
            'late' => $teamsData->where('status', 'late')->values(),
            'not_yet' => $teamsData->where('status', 'not_yet')->values(),
            default => $teamsData->values(),
        };

        return response()->json([
            'message' => 'Milestone teams retrieved successfully',
            'data' => [
                'academic_year' => [
                    'id' => $activeYear->id,
                    'code' => $activeYear->code,
                ],
                'milestone' => [
                    'id' => $milestone->id,
                    'title' => $milestone->title,
                    'phase_number' => $milestone->phase_number,
                    'status' => $milestone->status,
                    'start_date' => optional($milestone->start_date)?->format('Y-m-d'),
                    'deadline' => optional($milestone->deadline)?->format('Y-m-d'),
                ],
                'selected_type' => $type,
                'summary' => [
                    'all' => $teamsData->count(),
                    'submitted' => $teamsData->where('status', 'submitted')->count(),
                    'late' => $teamsData->where('status', 'late')->count(),
                    'not_yet' => $teamsData->where('status', 'not_yet')->count(),
                ],
                'teams' => $filteredTeams,
            ]
        ], 200);
    }
}