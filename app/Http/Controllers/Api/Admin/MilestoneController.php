<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\MilestonesRequest;
use App\Models\AcademicYear;
use App\Models\DatabaseNotification;
use App\Models\Milestone;
use App\Models\Team;
use App\Models\TeamMembership;
use App\Models\User;
use App\Notifications\MilestoneNoteNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use function PHPUnit\Framework\countOf;

class MilestoneController extends Controller
{
public function index()
{
    $milestones = Milestone::with('requirements')
        ->where('is_active', true)
        ->orderBy('phase_number')
        ->get();

    return response()->json([
        'message' => 'Milestones retrieved successfully',
        'count' => $milestones->count(),
        'data' => $milestones
    ], 200);
}
   public function store(MilestonesRequest $request)
{
    try {
        $milestone = DB::transaction(function () use ($request) {
            $phase_number = $this->calculateSortOrder($request->previous_milestone_id);
            $this->shiftMilestones($phase_number, 'increment');

            $milestone = Milestone::create([
                'title' => $request->title,
                'description' => $request->description ? $request->description : null,
                'phase_number' => $phase_number,
                'start_date' => $request->start_date,
                'deadline' => $request->deadline,
                'status' => 'pending',
                'is_open' => false,
                'is_active' => true,
                'is_forced_open' => false,
                'is_forced_closed' => false,
            ]);

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
  public function toggleOpenClose($id)
{
    try {
        $milestone = Milestone::findOrFail($id);

        // pending: ممنوع
        if ($milestone->status === 'pending') {
            return response()->json([
                'message' => 'Pending milestone cannot be changed'
            ], 400);
        }

        // completed -> reopen
        if ($milestone->status === 'completed') {
            $milestone->update([
                'is_forced_open' => true,
                'is_forced_closed' => false,
                'status' => 'on_progress',
                'is_open' => true,
            ]);

            return response()->json([
                'message' => 'Milestone reopened successfully',
                'data' => $milestone->fresh()->load('requirements')
            ], 200);
        }

        // on_progress -> close
        if ($milestone->status === 'on_progress') {
            $milestone->update([
                'is_forced_open' => false,
                'is_forced_closed' => true,
                'status' => 'completed',
                'is_open' => false,
            ]);

            return response()->json([
                'message' => 'Milestone closed successfully',
                'data' => $milestone->fresh()->load('requirements')
            ], 200);
        }

        return response()->json([
            'message' => 'Milestone cannot be changed in its current state'
        ], 400);

    } catch (\Throwable $th) {
        return response()->json([
            'message' => 'Milestone not found'
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

        $milestones = Milestone::with('requirements')
            ->where('is_active', true)
            ->where('status', $status)
            ->orderBy('phase_number')
            ->get();

        return response()->json([
            'message' => "$status Milestones retrieved successfully",
            'count' => $milestones->count(),
            'data' => $milestones
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
            'title' => $request->filled('title') ? $request->title : $milestone->title,
            'description' => $request->filled('description') ? $request->description : $milestone->description,
            'start_date' => $finalStartDate,
            'deadline' => $finalDeadline,
            'is_forced_open' => false,
            'is_forced_closed' => false,
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
public function toggleActive($id)
{
    try {
        $milestone = Milestone::findOrFail($id);

        $milestone->update([
            'is_active' => !$milestone->is_active
        ]);

        return response()->json([
            'message' => $milestone->is_active
                ? 'Milestone activated successfully'
                : 'Milestone deactivated successfully',
            'data' => $milestone->load('requirements')
        ], 200);

    } catch (\Throwable $th) {
        return response()->json([
            'message' => 'Milestone not found'
        ], 404);
    }
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

        // بعد update الـ milestone
$academicYear = AcademicYear::where('is_active', true)->first();

// جلب كل الفرق اللي عندها هذا الميلستون
$teams = Team::where('academic_year_id', $academicYear->id)->get();

foreach ($teams as $team) {
    $members = TeamMembership::where('team_id', $team->id)
        ->where('status', 'active')
        ->with('user')
        ->get();
    
    foreach ($members as $member) {
        if ($member->user) {
            DatabaseNotification::create([
                'id' => (string) Str::uuid(),
                'type' => 'milestone_note',
                'notifiable_type' => User::class,
                'notifiable_id' => $member->user->id,
                'academic_year_id' => $academicYear->id,
                'data' => [
                    'type' => 'milestone_note',
                    'milestone_id' => $milestone->id,
                    'milestone_title' => $milestone->title,
                    'note' => $request->note,
                    'message' => "New note added to milestone '{$milestone->title}'",
                    'icon' => 'clipboard',
                    'color' => 'blue',
                    'created_at' => now(),
                ],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}

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


}
