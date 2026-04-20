<?php

namespace App\Http\Controllers\Api\Proposal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\User\StoreProposalRequest;
use App\Models\AcademicYear;
use App\Models\Proposal;
use App\Models\ProjectRule;
use App\Models\TeamMembership;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProposalController extends Controller
{
    public function store(StoreProposalRequest $request)
    {
        $user = Auth::user();

        DB::beginTransaction();

        try {
            // 1) السنة الأكاديمية الفعالة
            $academicYear = AcademicYear::where('is_active', 1)->first();

            if (!$academicYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active academic year found'
                ], 404);
            }

            // 2) التأكد إن الطالب active في السنة الحالية
            $activeEnrollment = $user->enrollments()
                ->where('academic_year_id', $academicYear->id)
                ->where('status', 'active')
                ->first();

            if (!$activeEnrollment) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not allowed to submit a proposal in this academic year'
                ], 403);
            }

            // 3) نجيب الفريق في نفس السنة الحالية فقط
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
                    'message' => 'You are not in any team'
                ], 403);
            }

            $team = $membership->team;

            // 4) التأكد إن المستخدم هو الـ Leader الحالي
            if ((int) $team->leader_user_id !== (int) $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only the team leader can submit a proposal'
                ], 403);
            }

            // 5) التأكد من عدم وجود Proposal approved مسبقًا
            $existingProposal = Proposal::where('team_id', $team->id)->latest()->first();

            if ($existingProposal && $existingProposal->status === 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot modify an approved proposal'
                ], 403);
            }

            // 6) التأكد من عدد أعضاء الفريق
            $activeMembersCount = TeamMembership::where('team_id', $team->id)
                ->where('academic_year_id', $academicYear->id)
                ->where('status', 'active')
                ->count();

            $minTeamSize = ProjectRule::getMinTeamSize();
            $maxTeamSize = ProjectRule::getMaxTeamSize();

            if ($activeMembersCount < $minTeamSize) {
                return response()->json([
                    'success' => false,
                    'message' => "Your team must have at least {$minTeamSize} members before submitting a proposal. Current members: {$activeMembersCount}",
                    'required' => $minTeamSize,
                    'current' => $activeMembersCount,
                    'needed' => $minTeamSize - $activeMembersCount
                ], 400);
            }

            if ($activeMembersCount > $maxTeamSize) {
                return response()->json([
                    'success' => false,
                    'message' => "Your team exceeds the maximum allowed members ({$maxTeamSize}). Current members: {$activeMembersCount}"
                ], 400);
            }

            // 7) التأكد إن الليدر الجديد موجود في نفس الفريق ونفس السنة
            $isInTeam = TeamMembership::where('team_id', $team->id)
                ->where('academic_year_id', $academicYear->id)
                ->where('student_user_id', $request->leader_user_id)
                ->where('status', 'active')
                ->exists();

            if (!$isInTeam) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected leader is not a member of your team'
                ], 400);
            }

            // 8) تحديث leader في جدول teams
            $team->update([
                'leader_user_id' => $request->leader_user_id
            ]);

            // 9) تحديث roles في team_memberships
            TeamMembership::where('team_id', $team->id)
                ->where('academic_year_id', $academicYear->id)
                ->where('role_in_team', 'leader')
                ->update(['role_in_team' => 'member']);

            TeamMembership::where('team_id', $team->id)
                ->where('academic_year_id', $academicYear->id)
                ->where('student_user_id', $request->leader_user_id)
                ->update(['role_in_team' => 'leader']);

            // 10) رفع الملفات
            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $attachmentPath = $request->file('attachment')->store('proposals/attachments', 'public');
            }

            $imagePath = null;
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('proposals/images', 'public');
            }

            // 11) إنشاء أو تحديث الـ proposal
            $proposal = Proposal::updateOrCreate(
                ['team_id' => $team->id],
                [
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
                ]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Your Idea has been submitted successfully',
                'data' => [
                    'proposal_id' => $proposal->id,
                    'team_id' => $team->id,
                    'new_leader_id' => $request->leader_user_id,
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
                'message' => 'Error submitting proposal'.$e->getMessage()
            ], 500);
        }
    }
}