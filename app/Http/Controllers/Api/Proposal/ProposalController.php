<?php


namespace App\Http\Controllers\Api\Proposal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\User\StoreProposalRequest;
use App\Models\Proposal;
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
        // 1. نجيب الفريق بتاع العضو الحالي
        $membership = TeamMembership::where('student_user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (!$membership) {
            return response()->json([
                'success' => false,
                'message' => 'You are not in any team'
            ], 403);
        }

        $team = $membership->team;

        // 2. نتأكد إن الليدر الجديد موجود في نفس الفريق
        $isInTeam = TeamMembership::where('team_id', $team->id)
            ->where('student_user_id', $request->leader_user_id)
            ->where('status', 'active')
            ->exists();

        if (!$isInTeam) {
            return response()->json([
                'success' => false,
                'message' => 'Selected leader is not a member of your team'
            ], 400);
        }

        // 3. تحديث leader في جدول teams
        $team->update([
            'leader_user_id' => $request->leader_user_id
        ]);

        // 4. تحديث roles في team_memberships
        //    - إزالة leader القديم
        TeamMembership::where('team_id', $team->id)
            ->where('role_in_team', 'leader')
            ->update(['role_in_team' => 'member']);

        //    - تعيين leader الجديد
        TeamMembership::where('team_id', $team->id)
            ->where('student_user_id', $request->leader_user_id)
            ->update(['role_in_team' => 'leader']);

       $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('proposals/attachments', 'public');
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('proposals/images', 'public');
        }

        // 6. إنشاء proposal
        $proposal = Proposal::updateOrCreate([
            
        'team_id' => $team->id,
        'status' => 'pending'
         ],[
            'submitted_by_user_id' => $user->id,
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
            
        ]);

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
        if (isset($attachmentPath)) Storage::disk('public')->delete($attachmentPath);
        if (isset($imagePath)) Storage::disk('public')->delete($imagePath);

        return response()->json([
            'success' => false,
            'message' => 'Error submitting proposal',
            'error' => $e->getMessage()
        ], 500);
    }
}


}