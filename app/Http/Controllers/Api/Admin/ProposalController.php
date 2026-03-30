<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Proposal;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\In;

class ProposalController extends Controller
{
    public function requestStatus(Request $request,$id,$status)
    {
        try {
            if (!in_array($status, ['approved', 'rejected'])) {
                return response()->json([
                    'message' => 'Status must be approved or rejected'
                ], 422);
            }
            $proposal=Proposal::findOrFail($id);

                $proposal->update([
                    'status'=>$status
                ]);
                 return response()->json([
                        'message'=>"proposal $status successfuly",
                        'Proposal'=>$proposal
                    ],200);
        } catch (\Throwable $th) {
            return response()->json([
                        'message'=>$th->getMessage()
                    ],500);
        }
       

    }
}
