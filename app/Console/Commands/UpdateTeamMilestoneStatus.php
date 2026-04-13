<?php

namespace App\Console\Commands;

use App\Models\Milestone;
use App\Models\Team;
use App\Models\Submission;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UpdateTeamMilestoneStatus extends Command
{
    protected $signature = 'team-milestone:update';
    protected $description = 'Update team_milestone_status based on submissions and deadlines';

    public function handle()
    {
        $this->info('Starting team milestone status update...');
        
        $teams = Team::all();
        $milestones = Milestone::all();
        $now = now();
        
        $updatedCount = 0;
        
        foreach ($teams as $team) {
            foreach ($milestones as $milestone) {
                // جلب آخر submission للفريق في الميلستون دي
                $submission = Submission::where('milestone_id', $milestone->id)
                    ->where('team_id', $team->id)
                    ->latest('submitted_at')
                    ->first();
                
                if ($submission) {
                    // لو في submission، حسب الحالة من وقت التسليم
                    $deadline = Carbon::parse($milestone->deadline);
                    $submittedAt = Carbon::parse($submission->submitted_at);
                    $status = $submittedAt <= $deadline ? 'on_track' : 'delayed';
                } else {
                    // لو مفيش submission، حسب الحالة من النهاردة والـ deadline
                    $deadline = Carbon::parse($milestone->deadline);
                    $status = $now <= $deadline ? 'pending_submission' : 'delayed';
                }
                
                // تحديث أو إدراج الحالة
                DB::table('team_milestone_status')->updateOrInsert(
                    [
                        'team_id' => $team->id,
                        'milestone_id' => $milestone->id,
                    ],
                    [
                        'status' => $status,
                        'updated_at' => $now,
                    ]
                );
                
                $updatedCount++;
            }
        }
        
        $this->info("Updated {$updatedCount} team-milestone records successfully!");
    }
}