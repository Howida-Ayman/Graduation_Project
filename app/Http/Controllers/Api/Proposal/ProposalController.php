<?php

namespace App\Http\Controllers\Api\Proposal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\User\StoreProposalRequest;
use App\Models\AcademicYear;
use App\Models\Proposal;
use App\Models\ProjectRule;
use App\Models\TeamMembership;
use App\Models\TeamSupervisor;
use App\Models\DatabaseNotification;
use App\Models\ProjectCourse;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProposalController extends Controller
{
    private function hasActiveCapstoneEnrollment($user, $academicYear): bool
{
    return $user->enrollments()
        ->where('academic_year_id', $academicYear->id)
        ->where('status', 'in_progress')
        ->whereHas('projectCourse', function ($q) {
            $q->whereIn('order', [1, 2]);
        })
        ->exists();
}
    public function store(StoreProposalRequest $request)
{
    $user = Auth::user();

    DB::beginTransaction();

    try {
        $academicYear = AcademicYear::where('is_active', 1)->first();

        if (!$academicYear) {
            return response()->json([
                'success' => false,
                'message' => 'No active academic year found.'
            ], 404);
        }

        $project1 = ProjectCourse::where('order', 1)->first();

        if (!$project1) {
            return response()->json([
                'success' => false,
                'message' => 'Capstone Project I is not configured.'
            ], 422);
        }

        $activeEnrollment = $user->enrollments()
            ->where('academic_year_id', $academicYear->id)
            ->where('project_course_id', $project1->id)
            ->where('status', 'in_progress')
            ->first();

        if (!$activeEnrollment) {
            return response()->json([
                'success' => false,
                'message' => 'Only students enrolled in Capstone Project I can submit a proposal.'
            ], 403);
        }

        $membership = TeamMembership::where('student_user_id', $user->id)
            ->where('academic_year_id', $academicYear->id)
            ->where('status', 'active')
            ->first();

        if (!$membership) {
            return response()->json([
                'success' => false,
                'message' => 'You are not in any team.'
            ], 403);
        }

        $team = $membership->team;

        if (!$team) {
            return response()->json([
                'success' => false,
                'message' => 'Team not found.'
            ], 404);
        }

        if ((int) $team->leader_user_id !== (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Only the current team leader can submit a proposal.'
            ], 403);
        }

        $existingProposal = Proposal::where('team_id', $team->id)->exists();

        if ($existingProposal) {
            return response()->json([
                'success' => false,
                'message' => 'Your team has already submitted a proposal.'
            ], 403);
        }

        $newLeaderMembership = TeamMembership::where('team_id', $team->id)
            ->where('academic_year_id', $academicYear->id)
            ->where('student_user_id', $request->leader_user_id)
            ->where('status', 'active')
            ->first();

        if (!$newLeaderMembership) {
            return response()->json([
                'success' => false,
                'message' => 'Selected leader must be an active member of the team.'
            ], 422);
        }

        $activeMembersCount = TeamMembership::where('team_id', $team->id)
            ->where('academic_year_id', $academicYear->id)
            ->where('status', 'active')
            ->count();

        $minTeamSize = ProjectRule::getMinTeamSize();
        $maxTeamSize = ProjectRule::getMaxTeamSize();

        if ($activeMembersCount < $minTeamSize) {
            return response()->json([
                'success' => false,
                'message' => "Your team must have at least {$minTeamSize} members before submitting a proposal.",
                'required' => $minTeamSize,
                'current' => $activeMembersCount,
                'needed' => $minTeamSize - $activeMembersCount,
            ], 400);
        }

        if ($activeMembersCount > $maxTeamSize) {
            return response()->json([
                'success' => false,
                'message' => "Your team exceeds the maximum allowed members ({$maxTeamSize}).",
                'current' => $activeMembersCount,
            ], 400);
        }

        $oldLeaderId = $team->leader_user_id;

        if ((int) $oldLeaderId !== (int) $request->leader_user_id) {
            TeamMembership::where('team_id', $team->id)
                ->where('academic_year_id', $academicYear->id)
                ->where('student_user_id', $oldLeaderId)
                ->update([
                    'role_in_team' => 'member'
                ]);

            $newLeaderMembership->update([
                'role_in_team' => 'leader'
            ]);

            $team->update([
                'leader_user_id' => $request->leader_user_id
            ]);
        }

        $attachmentPath = null;
        $imagePath = null;

        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')
                ->store('proposals/attachments', 'public');
        }

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')
                ->store('proposals/images', 'public');
        }

        $proposal = Proposal::create([
            'team_id' => $team->id,
            'submitted_by_user_id' => $user->id,
            'academic_year_id' => $academicYear->id,
            'department_id' => $request->department_id,
            'project_type_id' => $request->project_type_id,
            'title' => $request->title,
            'description' => $request->description,
            'problem_statement' => $request->problem_statement,
            'solution' => $request->solution,
            'category' => $request->category,
            'technologies' => $request->technologies,
            'attachment_file' => $attachmentPath ? Storage::url($attachmentPath) : null,
            'image_url' => $imagePath ? Storage::url($imagePath) : null,
            'status' => 'pending',
        ]);

        $admins = User::where('role_id', 1)
            ->where('is_active', true)
            ->get();

        foreach ($admins as $admin) {
            DatabaseNotification::create([
                'id' => (string) Str::uuid(),
                'type' => 'proposal_submitted',
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id' => $admin->id,
                'academic_year_id' => $academicYear->id,
                'data' => [
                    'type' => 'proposal_submitted',
                    'proposal_id' => $proposal->id,
                    'proposal_title' => $proposal->title,
                    'team_id' => $team->id,
                    'team_name' => $team->name ?? "Team {$team->id}",
                    'message' => "Team '" . ($team->name ?? "Team {$team->id}") . "' has submitted a new project idea: '{$proposal->title}'",
                    'icon' => 'file-text',
                    'color' => 'blue',
                    'created_at' => now(),
                ],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Proposal submitted successfully.',
            'data' => [
                'proposal_id' => $proposal->id,
                'team_id' => $team->id,
                'submitted_by_user_id' => $user->id,
                'leader_user_id' => $team->fresh()->leader_user_id,
                'status' => $proposal->status,
            ]
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();

        if (isset($attachmentPath) && $attachmentPath) {
            Storage::disk('public')->delete($attachmentPath);
        }

        if (isset($imagePath) && $imagePath) {
            Storage::disk('public')->delete($imagePath);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to submit proposal.',
            'error' => config('app.debug') ? $e->getMessage() : null,
        ], 500);
    }
}
}