<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\MilestonesRequest;
use App\Models\Milestone;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

use function PHPUnit\Framework\countOf;

class MilestoneController extends Controller
{
    public function index()
    {
    $today = Carbon::today(); // 2026-03-17 00:00:00
    
    // نجيب الكل
    $milestones = Milestone::with('requirements')->get();
    
    foreach ($milestones as $milestone) {
        $startDate = Carbon::parse($milestone->start_date);
        $deadline = Carbon::parse($milestone->deadline);

        $newStatus = null;
        $is_open = false;
        
        if ($startDate <= $today && $deadline >= $today) {
            $newStatus = 'on_progress';
            $is_open=true;
        } elseif ($deadline < $today) {
            $newStatus = 'completed';
            $is_open=false;
        } elseif ($startDate > $today) {
            $newStatus = 'pending';
            $is_open=false;
        }
        // لو الحالة اتغيرت، حدث
        if ($newStatus && $milestone->status != $newStatus) {
            $milestone->update(['status' => $newStatus,'is_open'=>$is_open]);
        }
    }
            $updatedmilestones=Milestone::with('requirements')->orderBy('phase_number')->get();
        return response()->json([
                'message' => 'Milestones retrieved successfully',
                'count'=>count($milestones),
                'data' => $updatedmilestones->load('requirements')
            ], 200);

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
   public function reopen($id)
{
    try {
        $milestone = Milestone::findOrFail($id);
        
        // لو milestone مقفول ومكتمل -> نفتحه تاني
        if ($milestone->is_open == false && $milestone->status == 'completed') {
            $milestone->update([
                'status' => 'on_progress',
                'is_open' => true,
            ]);
            
            return response()->json([
                'message' => 'Milestone reopened successfully',
                'data' => $milestone
            ], 200);
        }
        
        // لو milestone مفتوح وفي progress -> نقفله
        if ($milestone->is_open == true && $milestone->status == 'on_progress') {
            $milestone->update([
                'status' => 'completed',
                'is_open' => false,
            ]);
            
            return response()->json([
                'message' => 'Milestone completed successfully',
                'data' => $milestone
            ], 200);
        }
        
        // لو مش eligible للتغيير
        return response()->json([
            'message' => 'Milestone cannot be changed in its current state',
            'current_state' => [
                'status' => $milestone->status,
                'is_open' => $milestone->is_open
            ]
        ], 400);
        
    } catch (\Throwable $th) {
        return response()->json([
            'message' => 'Milestone Not Found',
        ], 404);
    }
}
public function milestonesByStatus($status)
{
    try {
        $allowedStatuses = ['pending', 'on_progress', 'completed'];
        
        if ($status && !in_array($status, $allowedStatuses)) {
            return response()->json([
                'message' => 'Invalid status',
                'allowed_statuses' => $allowedStatuses
            ], 400);
        }
        $milestones=Milestone::with('requirements')->orderBy('phase_number')->where('status',$status)->get();
            return response()->json([
                'message' => "$status Milestones retrieved successfully",
                'count'=>count($milestones),
                'data' => $milestones->load('requirements')
            ], 200);
    } catch (\Throwable $th) {
        return response()->json([
                'message' => 'Failed to retrieve milestones.',
            ], 500);
    }
}
public function update(Request $request,$id)
{
    try {
        $milestone=DB::transaction(function() use($id, $request)
        {
            $milestone=Milestone::with('requirements')->findOrFail($id);
            $request->validate([
            'previous_milestone_id' => 'nullable|exists:milestones,id',
            'title'=>'nullable|string|unique:milestones,title,'.$milestone->id,
            'description'=>'nullable|string',
            'start_date' => 'nullable|date',
            'deadline' => 'nullable|date',
            'requirements' => 'nullable|array',
            'requirements.*.id' => 'nullable|exists:milestone_requirements,id', // للـ update
            'requirements.*.text' => 'required_with:requirements.*|string|max:500',
            'requirements.*.action' => 'nullable|in:update,delete,new' // تحديد نوع العملية
            ]);
            $finalStartDate = $request->filled('start_date')
             ? $request->start_date
             : $milestone->start_date;

            $finalDeadline = $request->filled('deadline')
            ? $request->deadline
            : $milestone->deadline;

          if ($finalStartDate && $finalDeadline) {
            if (strtotime($finalDeadline) <= strtotime($finalStartDate)) {
             throw \Illuminate\Validation\ValidationException::withMessages([
            'deadline' => ['The deadline must be after the start date.']
          ]);
    }
}
            $milestone->update([
                'title'=>$request->filled('title')?$request->title:$milestone->title,
                'description'=>$request->filled('description')?$request->description:$milestone->description,
                'start_date'=>$finalStartDate,
                'deadline'=>$finalDeadline,
            ]);
            // 3. لو في previous_milestone_id (إعادة ترتيب)
            if ($request->has('previous_milestone_id')) {
                $this->reorderMilestoneOnUpdate($milestone, $request->previous_milestone_id);
            }
            // 2. تحديث الـ requirements لو موجودة
            if ($request->has('requirements')) {
                $this->updateRequirements($milestone, $request->requirements);
            }
           return $milestone->load('requirements');
        });
        return response()->json([
            'message' => 'Milestone updated successfully',
            'data' => $milestone
        ], 200);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'message' => 'Validation failed',
            'errors' => $e->errors()
        ], 422);
}catch (\Throwable $th) {
        return response()->json([
            'message' => 'Failed to update milestone',
            'error' => config('app.debug') ? $th->getMessage() : null
        ], 500);
    }
}
/**
 * تحديث المتطلبات
 */
private function updateRequirements($milestone, array $requirements)
{
    $existingIds = $milestone->requirements->pluck('id')->toArray();
    $processedIds = [];
    
    foreach ($requirements as $req) {
        // حالة: إضافة جديد
        if (!isset($req['id']) || $req['action'] === 'new') {
            $milestone->requirements()->create([
                'requirement' => $req['text']
            ]);
        }
        
        // حالة: تحديث موجود
        elseif ($req['action'] === 'update' && in_array($req['id'], $existingIds)) {
            $milestone->requirements()
                ->where('id', $req['id'])
                ->update(['requirement' => $req['text']]);
            $processedIds[] = $req['id'];
        }
        
        // حالة: حذف
        elseif ($req['action'] === 'delete' && in_array($req['id'], $existingIds)) {
            $milestone->requirements()->where('id', $req['id'])->delete();
        }
    }
}
/**
 * إعادة ترتيب الـ milestone
 */

private function reorderMilestoneOnUpdate($milestone, $previousMilestoneId)
{
    $oldPhaseNumber = $milestone->phase_number;

    if (!$previousMilestoneId) {
        $newPhaseNumber = 1;
    } else {
        $previousMilestone = Milestone::findOrFail($previousMilestoneId);

        if ($previousMilestone->id == $milestone->id) {
            throw new \Exception('previous_milestone_id cannot be the same as milestone id');
        }

        $newPhaseNumber = $previousMilestone->phase_number + 1;
    }

    if ($newPhaseNumber == $oldPhaseNumber) {
        return;
    }

    // إخراج الميلستون الحالي مؤقتًا من التسلسل
    $milestone->update([
        'phase_number' => 9999
    ]);

    if ($newPhaseNumber < $oldPhaseNumber) {
        // نقل لأعلى
        Milestone::where('id', '!=', $milestone->id)
            ->where('phase_number', '>=', $newPhaseNumber)
            ->where('phase_number', '<', $oldPhaseNumber)
            ->update([
                'phase_number' => DB::raw('phase_number + 1000')
            ]);

        Milestone::where('phase_number', '>=', $newPhaseNumber + 1000)
            ->where('phase_number', '<', $oldPhaseNumber + 1000)
            ->update([
                'phase_number' => DB::raw('phase_number - 999')
            ]);
    } else {
        // نقل لأسفل
        Milestone::where('id', '!=', $milestone->id)
            ->where('phase_number', '>', $oldPhaseNumber)
            ->where('phase_number', '<=', $newPhaseNumber)
            ->update([
                'phase_number' => DB::raw('phase_number + 1000')
            ]);

        Milestone::where('phase_number', '>', $oldPhaseNumber + 1000)
            ->where('phase_number', '<=', $newPhaseNumber + 1000)
            ->update([
                'phase_number' => DB::raw('phase_number - 1001')
            ]);
    }

    $milestone->update([
        'phase_number' => $newPhaseNumber
    ]);
}
public function destroy($id)
{
    $milestone=Milestone::findOrFail($id)->delete();
    return response()->json([
        'message' => 'Milestones Deleted successfully'
            ], 200);
}
public function storeNote(Request $request, $milestone_id)
{
    try {
        $request->validate([
            'note' => 'nullable|string'
        ]);

        $milestone = Milestone::findOrFail($milestone_id);

        $milestone->update([
            'notes' => $request->note
        ]);

        return response()->json([
            'message' => 'Note added successfully',
            'data' => $milestone->load('requirements')
        ], 200);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'message' => 'Milestone not found'
        ], 404);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'message' => 'Validation failed',
            'errors' => $e->errors()
        ], 422);

    } catch (\Throwable $th) {
        return response()->json([
            'message' => 'Failed to update milestone',
            'error' => config('app.debug') ? $th->getMessage() : null
        ], 500);
    }
}

public function deleteNote(Request $request,$id)
{
    
}
}
