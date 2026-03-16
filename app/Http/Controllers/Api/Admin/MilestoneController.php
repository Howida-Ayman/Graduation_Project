<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\MilestonesRequest;
use App\Models\Milestone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Stmt\TryCatch;

class MilestoneController extends Controller
{
    public function index()
    {

    }
    public function store(MilestonesRequest $request)
    {
        try {
            
            $milestone=DB::transaction(function() use($request)
            {
               $phase_number=$this->calculateSortOrder($request->previous_milestone_id);
               $this->shiftMilestones($phase_number,'increment');
               $milestone=Milestone::create([
                'title'=>$request->title,
                'description'=>$request->description?$request->description:null,
                'phase_number' => $phase_number, 
                'start_date' => $request->start_date,
                'deadline' => $request->deadline,
                'status' => 'pending',
                'is_open' => true
               ]);
               // 4. إضافة المتطلبات
                if ($request->has('requirements')) {
                    foreach ($request->requirements as $requirement) {
                        $milestone->requirements()->create([
                            'requirement' => $requirement
                        ]);
                    }
                }
            return $milestone;
            });
            return response()->json([
                'message' => 'Milestone created successfully',
                'data' => $milestone->load('requirements')
            ], 201);
        } catch (\Throwable $th) {
               return response()->json([
                'message' => 'Failed to create milestone',
                'error' => $th->getMessage()
            ], 500);
        }
        }
    
    private function calculateSortOrder($previousMilestoneId)
    {
        if(!$previousMilestoneId)
            {
                return 1;
            }
        $previousMilestoneId=Milestone::where('id',$previousMilestoneId)->firstOrFail();
        return $previousMilestoneId->phase_number+1;
    }
    /**
     * إزاحة المراحل (زيادة أو نقصان)
     */
    private function shiftMilestones($fromPhaseNumber, $direction = 'increment')
    {
        $milestones = Milestone::where('phase_number', '>=', $fromPhaseNumber)
            ->orderBy('phase_number', 'desc') // مهم نبدأ من الآخر عشان ما نعملش duplicate
            ->get();
        
        foreach ($milestones as $milestone) {
            if ($direction === 'increment') {
                $milestone->phase_number += 1;  
            } else {
                $milestone->phase_number -= 1;
            }
            $milestone->save();
        }
    }

}
