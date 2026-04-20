<?php

namespace App\Http\Controllers\Api\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Meeting;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MeetingController extends Controller
{
    public function teamsList(Request $request)
    {
        $user = $request->user();

        $activeYear = AcademicYear::where('is_active', true)->first();

        if (!$activeYear) {
            return response()->json([
                'message' => 'No active academic year found'
            ], 404);
        }

        if (!in_array((int) $user->role_id, [2, 3])) {
            return response()->json([
                'message' => 'Only doctors and TAs can access meetings'
            ], 403);
        }

        $teams = Team::query()
            ->where('academic_year_id', $activeYear->id)
            ->whereHas('currentSupervisors', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            })
            ->with('graduationProject.proposal')
            ->get();

        return response()->json([
            'message' => 'Teams retrieved successfully',
            'data' => $teams->map(function ($team) {
                return [
                    'team_id' => $team->id,
                    'project_title' => $team->graduationProject?->proposal?->title,
                ];
            })->values()
        ], 200);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $todayStart = now()->startOfDay();

        $activeYear = AcademicYear::where('is_active', true)->first();

        if (!$activeYear) {
            return response()->json([
                'message' => 'No active academic year found'
            ], 404);
        }

        if (!in_array((int) $user->role_id, [2, 3])) {
            return response()->json([
                'message' => 'Only doctors and TAs can access meetings'
            ], 403);
        }

        $meetings = Meeting::query()
            ->with(['team.graduationProject.proposal'])
            ->where('created_by_user_id', $user->id)
            ->where('scheduled_at', '>=', $todayStart)
            ->whereHas('team', function ($q) use ($activeYear, $user) {
                $q->where('academic_year_id', $activeYear->id)
                  ->whereHas('currentSupervisors', function ($sq) use ($user) {
                      $sq->where('users.id', $user->id);
                  });
            })
            ->orderBy('scheduled_at', 'asc')
            ->get();

        return response()->json([
            'message' => 'Meetings retrieved successfully',
            'data' => $meetings->map(function ($meeting) {
                return [
                    'meeting_id' => $meeting->id,
                    'team_id' => $meeting->team_id,
                    'project_title' => $meeting->team?->graduationProject?->proposal?->title,
                    'scheduled_at' => $meeting->scheduled_at?->format('Y-m-d H:i:s'),
                    'date' => $meeting->scheduled_at?->format('Y-m-d'),
                    'time' => $meeting->scheduled_at?->format('H:i'),
                    'meeting_link' => $meeting->meeting_link,
                    'is_today' => $meeting->scheduled_at?->isToday() ?? false,
                ];
            })->values()
        ], 200);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $activeYear = AcademicYear::where('is_active', true)->first();

        if (!$activeYear) {
            return response()->json([
                'message' => 'No active academic year found'
            ], 404);
        }

        if (!in_array((int) $user->role_id, [2, 3])) {
            return response()->json([
                'message' => 'Only doctors and TAs can create meetings'
            ], 403);
        }

        $validated = $request->validate([
            'team_id' => 'required|exists:teams,id',
            'scheduled_at' => [
                'required',
                'date',
                function ($attribute, $value, $fail) {
                    if (Carbon::parse($value)->lt(now())) {
                        $fail('The meeting date must be today or in the future.');
                    }
                }
            ],
            'meeting_link' => 'nullable|string|max:3000',
        ]);

        $team = Team::query()
            ->where('id', $validated['team_id'])
            ->where('academic_year_id', $activeYear->id)
            ->whereHas('currentSupervisors', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            })
            ->with('graduationProject.proposal')
            ->first();

        if (!$team) {
            return response()->json([
                'message' => 'You are not authorized to create a meeting for this team'
            ], 403);
        }

        $scheduledAt = Carbon::parse($validated['scheduled_at']);

        $hasConflict = Meeting::query()
            ->where('created_by_user_id', $user->id)
            ->where('scheduled_at', $scheduledAt)
            ->exists();

        if ($hasConflict) {
            return response()->json([
                'message' => 'You already have another meeting scheduled at the same time'
            ], 422);
        }

        $meeting = Meeting::create([
            'academic_year_id'=>$activeYear->id,
            'team_id' => $team->id,
            'created_by_user_id' => $user->id,
            'scheduled_at' => $scheduledAt,
            'meeting_link' => $validated['meeting_link'] ?? null,
        ]);

        log_activity(
            teamId: $team->id,
            userId: $user->id,
            action: 'meeting_created',
            message: 'Meeting scheduled for project "' . ($team->graduationProject?->proposal?->title ?? 'Unknown Project') . '"',
            meta: [
                'meeting_id' => $meeting->id,
                'scheduled_at' => $meeting->scheduled_at?->format('Y-m-d H:i:s'),
            ]
        );

        return response()->json([
            'message' => 'Meeting created successfully',
            'data' => [
                'meeting_id' => $meeting->id,
                'team_id' => $meeting->team_id,
                'project_title' => $team->graduationProject?->proposal?->title,
                'scheduled_at' => $meeting->scheduled_at?->format('Y-m-d H:i:s'),
                'meeting_link' => $meeting->meeting_link,
            ]
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();

        $activeYear = AcademicYear::where('is_active', true)->first();

        if (!$activeYear) {
            return response()->json([
                'message' => 'No active academic year found'
            ], 404);
        }

        if (!in_array((int) $user->role_id, [2, 3])) {
            return response()->json([
                'message' => 'Only doctors and TAs can update meetings'
            ], 403);
        }

        $validated = $request->validate([
            'scheduled_at' => [
                'sometimes',
                'required',
                'date',
                function ($attribute, $value, $fail) {
                    if (Carbon::parse($value)->lt(now())) {
                        $fail('The meeting date must be today or in the future.');
                    }
                }
            ],
            'meeting_link' => 'nullable|string|max:3000',
        ]);

        $meeting = Meeting::query()
            ->with(['team.graduationProject.proposal'])
            ->where('id', $id)
            ->where('created_by_user_id', $user->id)
            ->whereHas('team', function ($q) use ($activeYear, $user) {
                $q->where('academic_year_id', $activeYear->id)
                  ->whereHas('currentSupervisors', function ($sq) use ($user) {
                      $sq->where('users.id', $user->id);
                  });
            })
            ->first();

        if (!$meeting) {
            return response()->json([
                'message' => 'Meeting not found or you are not authorized to update it'
            ], 404);
        }

        $newScheduledAt = $request->has('scheduled_at')
            ? Carbon::parse($validated['scheduled_at'])
            : $meeting->scheduled_at;

        $hasConflict = Meeting::query()
            ->where('created_by_user_id', $user->id)
            ->where('scheduled_at', $newScheduledAt)
            ->where('id', '!=', $meeting->id)
            ->exists();

        if ($hasConflict) {
            return response()->json([
                'message' => 'You already have another meeting scheduled at the same time'
            ], 422);
        }

        $meeting->update([
            'scheduled_at' => $newScheduledAt,
            'meeting_link' => $request->has('meeting_link')
                ? $validated['meeting_link']
                : $meeting->meeting_link,
        ]);

        log_activity(
            teamId: $meeting->team_id,
            userId: $user->id,
            action: 'meeting_updated',
            message: 'Meeting updated for project "' . ($meeting->team?->graduationProject?->proposal?->title ?? 'Unknown Project') . '"',
            meta: [
                'meeting_id' => $meeting->id,
                'scheduled_at' => $meeting->scheduled_at?->format('Y-m-d H:i:s'),
            ]
        );

        return response()->json([
            'message' => 'Meeting updated successfully',
            'data' => [
                'meeting_id' => $meeting->id,
                'team_id' => $meeting->team_id,
                'project_title' => $meeting->team?->graduationProject?->proposal?->title,
                'scheduled_at' => $meeting->scheduled_at?->format('Y-m-d H:i:s'),
                'meeting_link' => $meeting->meeting_link,
            ]
        ], 200);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $activeYear = AcademicYear::where('is_active', true)->first();

        if (!$activeYear) {
            return response()->json([
                'message' => 'No active academic year found'
            ], 404);
        }

        if (!in_array((int) $user->role_id, [2, 3])) {
            return response()->json([
                'message' => 'Only doctors and TAs can delete meetings'
            ], 403);
        }

        $meeting = Meeting::query()
            ->with(['team.graduationProject.proposal'])
            ->where('id', $id)
            ->where('created_by_user_id', $user->id)
            ->whereHas('team', function ($q) use ($activeYear, $user) {
                $q->where('academic_year_id', $activeYear->id)
                  ->whereHas('currentSupervisors', function ($sq) use ($user) {
                      $sq->where('users.id', $user->id);
                  });
            })
            ->first();

        if (!$meeting) {
            return response()->json([
                'message' => 'Meeting not found or you are not authorized to delete it'
            ], 404);
        }

        log_activity(
            teamId: $meeting->team_id,
            userId: $user->id,
            action: 'meeting_deleted',
            message: 'Meeting deleted for project "' . ($meeting->team?->graduationProject?->proposal?->title ?? 'Unknown Project') . '"',
            meta: [
                'meeting_id' => $meeting->id,
                'scheduled_at' => $meeting->scheduled_at?->format('Y-m-d H:i:s'),
            ]
        );

        $meeting->delete();

        return response()->json([
            'message' => 'Meeting deleted successfully'
        ], 200);
    }
}