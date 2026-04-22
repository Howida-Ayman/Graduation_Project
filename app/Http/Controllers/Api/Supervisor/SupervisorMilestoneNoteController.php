<?php

namespace App\Http\Controllers\Api\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Milestone;
use App\Models\SupervisorMilestoneNote;
use App\Models\Team;
use App\Models\TeamMembership;
use Illuminate\Http\Request;

class SupervisorMilestoneNoteController extends Controller
{
    /**
     * حفظ أو تعديل النوت
     */
    public function storeOrUpdate(Request $request, $milestoneId)
    {
        $user = $request->user();

        $request->validate([
            'note' => 'required|string|max:5000',
        ]);

        $activeYear = AcademicYear::where('is_active', true)->first();

        if (!$activeYear) {
            return response()->json([
                'message' => 'No active academic year found'
            ], 404);
        }

        $milestone = Milestone::findOrFail($milestoneId);

       if ($milestone->status === 'completed') {
         return response()->json([
           'message' => 'You cannot add or update notes for a completed milestone'
         ], 403);
        } 

        // لازم يكون دكتور
        if ((int) $user->role_id !== 2) {
            return response()->json([
                'message' => 'Only doctors can add milestone notes'
            ], 403);
        }

        // لازم يكون عنده على الأقل team واحدة في السنة الفعالة على نفس النظام
        $hasTeams = Team::query()
            ->where('academic_year_id', $activeYear->id)
            ->whereHas('currentSupervisors', function ($q) use ($user) {
                $q->where('users.id', $user->id)
                  ->where('team_supervisors.supervisor_role', 'doctor');
            })
            ->exists();

        if (!$hasTeams) {
            return response()->json([
                'message' => 'You are not supervising any teams in the active academic year'
            ], 403);
        }

        $note = SupervisorMilestoneNote::updateOrCreate(
            [
                'academic_year_id' => $activeYear->id,
                'milestone_id' => $milestone->id,
                'supervisor_user_id' => $user->id,
            ],
            [
                'note' => $request->note,
            ]
        );

        // بعد updateOrCreate
$academicYear = AcademicYear::where('is_active', true)->first();

if ($academicYear) {
    // جلب كل الفرق اللي الدكتور بيشرف عليها في السنة دي
    $teams = Team::where('academic_year_id', $academicYear->id)
        ->whereHas('currentSupervisors', function ($q) use ($user) {
            $q->where('users.id', $user->id)
              ->where('team_supervisors.supervisor_role', 'doctor');
        })
        ->get();

    foreach ($teams as $team) {
        $members = TeamMembership::where('team_id', $team->id)
            ->where('status', 'active')
            ->with('user')
            ->get();

        foreach ($members as $member) {
            if ($member->user) {
                \App\Models\DatabaseNotification::create([
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'type' => 'doctor_milestone_note',
                    'notifiable_type' => 'App\\Models\\User',
                    'notifiable_id' => $member->user->id,
                    'academic_year_id' => $academicYear->id,
                    'data' => [
                        'type' => 'doctor_milestone_note',
                        'milestone_id' => $milestone->id,
                        'milestone_title' => $milestone->title,
                        'doctor_name' => $user->full_name,
                        'note' => $request->note,
                        'message' => "Dr. {$user->full_name} added a note on milestone '{$milestone->title}'",
                        'icon' => 'clipboard',
                        'color' => 'purple',
                        'created_at' => now(),
                    ],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}

        return response()->json([
            'message' => 'Milestone note saved successfully',
            'data' => [
                'id' => $note->id,
                'academic_year_id' => $note->academic_year_id,
                'milestone_id' => $note->milestone_id,
                'supervisor_user_id' => $note->supervisor_user_id,
                'note' => $note->note,
                'updated_at' => $note->updated_at,
            ]
        ], 200);
    }

    /**
     * جلب النوت بتاعة الدكتور على milestone معينة
     */
    public function show(Request $request, $milestoneId)
    {
        $user = $request->user();

        $activeYear = AcademicYear::where('is_active', true)->first();

        if (!$activeYear) {
            return response()->json([
                'message' => 'No active academic year found'
            ], 404);
        }

        $milestone = Milestone::findOrFail($milestoneId);

        $note = SupervisorMilestoneNote::where('academic_year_id', $activeYear->id)
            ->where('milestone_id', $milestone->id)
            ->where('supervisor_user_id', $user->id)
            ->first();

        return response()->json([
            'message' => 'Milestone note retrieved successfully',
            'data' => [
                'milestone_id' => $milestone->id,
                'note' => $note?->note,
                'updated_at' => $note?->updated_at,
            ]
        ], 200);
    }
}