<?php

namespace App\Http\Controllers\Api\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Team;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function TeamsList(Request $request)
    {
        $user = $request->user();

        $teams = Team::query()
            ->whereHas('supervisors', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            })
            ->with(['graduationProject.proposal'])
            ->get();

        $data = $teams->map(function ($team) {
            return [
                'team_id' => $team->id,
                'project_title' => $team->graduationProject?->proposal?->title,
            ];
        })->values();

        return response()->json([
            'message' => 'Projects retrieved successfully',
            'data' => $data,
        ], 200);
    }
    public function index(Request $request)
{
    $user = $request->user();

    $announcements = Announcement::query()
        ->with(['team.graduationProject.proposal'])
        ->where('sent_by_user_id', $user->id)
        ->latest()
        ->get()
        ->map(function ($announcement) {
            return [
                'team_id' => $announcement->team_id,
                'project_title' => $announcement->team?->graduationProject?->proposal?->title,
                'announcement_id' => $announcement->id,
                'message' => $announcement->message,
                'sent_at' => $announcement->created_at?->format('Y-m-d H:i:s'),
            ];
        })
        ->values();

    return response()->json([
        'message' => 'Announcements retrieved successfully',
        'data' => $announcements,
    ], 200);
}
    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'send_to' => 'required|in:all_teams,single_team',
            'team_id' => 'nullable|required_if:send_to,single_team|exists:teams,id',
            'message' => 'required|string|max:3000',
        ]);

        // هات كل التيمات اللي الدكتور الحالي مشرف عليها
        $supervisedTeamsQuery = Team::query()
            ->whereHas('currentSupervisors', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });

        if ($validated['send_to'] === 'single_team') {
            $team = (clone $supervisedTeamsQuery)
                ->where('id', $validated['team_id'])
                ->first();

            if (! $team) {
                return response()->json([
                    'message' => 'You are not authorized to send announcement to this team.'
                ], 403);
            }

            $announcement = Announcement::create([
                'team_id' => $team->id,
                'sent_by_user_id' => $user->id,
                'message' => $validated['message'],
            ]);

            // optional activity log
            log_activity(
                teamId: $team->id,
                userId: $user->id,
                action: 'announcement_sent',
                message: 'Announcement sent to project "' . ($team->graduationProject?->proposal?->title ?? 'Unknown Project') . '"',
                meta: [
                    'announcement_id' => $announcement->id,
                ]
            );

            return response()->json([
                'message' => 'Announcement sent successfully.',
                'data' => [
                    'sent_to' => 'single_team',
                    'team_id' => $team->id,
                    'announcement_id' => $announcement->id,
                    'message' => $announcement->message,
                    'created_at' => $announcement->created_at,
                ]
            ], 201);
        }

        $teams = $supervisedTeamsQuery->get();

        if ($teams->isEmpty()) {
            return response()->json([
                'message' => 'No supervised teams found.'
            ], 404);
        }

        $rows = [];
        $now = now();

        foreach ($teams as $team) {
            $rows[] = [
                'team_id' => $team->id,
                'sent_by_user_id' => $user->id,
                'message' => $validated['message'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        Announcement::insert($rows);

        foreach ($teams as $team) {
            log_activity(
                teamId: $team->id,
                userId: $user->id,
                action: 'announcement_sent',
                message: 'Announcement sent to project "' . ($team->graduationProject?->proposal?->title ?? 'Unknown Project') . '"',
                meta: [
                    'broadcast' => true,
                ]
            );
        }

        return response()->json([
            'message' => 'Announcement sent successfully to all supervised teams.',
            'data' => [
                'sent_to' => 'all_teams',
                'teams_count' => $teams->count(),
                'message' => $validated['message'],
            ]
        ], 201);
    }
}