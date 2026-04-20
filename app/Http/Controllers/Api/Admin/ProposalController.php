<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\DatabaseNotification;
use App\Models\Proposal;
use App\Models\TeamMembership;
use App\Models\User;
use App\Notifications\ProposalStatusNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProposalController extends Controller
{
    public function requestStatus(Request $request, $id, $status)
    {
        try {
            // 1) validation للـ status
            if (!in_array($status, ['approved', 'rejected'])) {
                return response()->json([
                    'message' => 'Status must be approved or rejected'
                ], 422);
            }

            // 2) السنة الفعالة
            $academicYear = AcademicYear::where('is_active', 1)->first();

            if (!$academicYear) {
                return response()->json([
                    'message' => 'No active academic year found'
                ], 404);
            }

            // 3) proposal في نفس السنة
            $proposal = Proposal::where('id', $id)
                ->whereHas('team', function ($q) use ($academicYear) {
                    $q->where('academic_year_id', $academicYear->id);
                })
                ->first();

            if (!$proposal) {
                return response()->json([
                    'message' => 'Proposal not found in active academic year'
                ], 404);
            }

            // 4) check الحالة الحالية
            if ($proposal->status !== 'pending') {
                return response()->json([
                    'message' => 'Only pending proposals can be updated'
                ], 400);
            }

            // 5) update
            $proposal->update([
                'status' => $status,
            ]);

            // بعد update الـ proposal
if ($status === 'approved' || $status === 'rejected') {
    $academicYear = AcademicYear::where('is_active', true)->first();
    $team = $proposal->team;
    
    $members = TeamMembership::where('team_id', $team->id)
        ->where('status', 'active')
        ->with('user')
        ->get();
    
    foreach ($members as $member) {
        if ($member->user) {
            DatabaseNotification::create([
                'id' => (string) Str::uuid(),
                'type' => 'proposal_' . $status,
                'notifiable_type' => User::class,
                'notifiable_id' => $member->user->id,
                'academic_year_id' => $academicYear->id,
                'data' => [
                    'type' => 'proposal_' . $status,
                    'proposal_id' => $proposal->id,
                    'proposal_title' => $proposal->title,
                    'team_id' => $team->id,
                    'message' => $status === 'approved' 
                        ? "Your project idea '{$proposal->title}' has been approved!"
                        : "Your project idea '{$proposal->title}' has been rejected.",
                    'icon' => $status === 'approved' ? 'check-circle' : 'x-circle',
                    'color' => $status === 'approved' ? 'green' : 'red',
                    'created_at' => now(),
                ],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}

            return response()->json([
                'message' => "Proposal {$status} successfully",
                'data' => $proposal
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Something went wrong'
            ], 500);
        }
    }
}