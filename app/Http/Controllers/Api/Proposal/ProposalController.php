<?php
// app/Http/Controllers/Api/Proposal/SubmitProposalController.php

namespace App\Http\Controllers\Api\Proposal;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProposalRequest;
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
            // 1 التأكد إن الطالب في فريق
            $membership = TeamMembership::where('student_user_id', $user->id)
                ->where('status', 'active')
                ->first();

            if (!$membership) {
                return response()->json([
                    'success' => false,
                    'message' => 'You must be in a team to submit a proposal'
                ], 403);
            }

            // 2 التأكد إن الفريق مقدمش proposal قبل كده
            $existingProposal = Proposal::where('team_id', $membership->team_id)
                ->whereIn('status', ['pending', 'approved'])
                ->first();

            if ($existingProposal) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your team already has a pending or approved proposal'
                ], 400);
            }

            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $attachmentPath = $request->file('attachment')->store('proposals/attachments', 'public');
            }

            $imagePath = null;
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('proposals/images', 'public');
            }

            $proposal = Proposal::create([
                'team_id' => $membership->team_id,
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
                'status' => 'pending',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Your Idea has been submitted successfully !',
                'data' => [
                    'proposal_id' => $proposal->id,
                    'status' => $proposal->status,
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            // حذف الملفات لو حصل خطأ
            if (isset($attachmentPath)) {
                Storage::disk('public')->delete($attachmentPath);
            }
            if (isset($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error submitting Idea',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}