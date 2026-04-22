<?php

namespace App\Console\Commands;

use App\Models\AcademicYear;
use App\Models\DatabaseNotification;
use App\Models\Milestone;
use App\Models\Submission;
use App\Models\Team;
use App\Models\TeamMembership;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MilestoneNotificationsCommand extends Command
{
    protected $signature = 'milestones:notify';
    protected $description = 'Send notifications for milestone start and upcoming deadlines';

    public function handle()
    {
        $academicYear = AcademicYear::where('is_active', true)->first();

        if (!$academicYear) {
            $this->error('No active academic year found');
            return 1;
        }

        $today = Carbon::today();

        // 1. الميليستونز اللي لسه بادئة النهاردة
        $startingMilestones = Milestone::whereDate('start_date', $today)
            ->where('status', 'pending')
            ->get();

        foreach ($startingMilestones as $milestone) {
            // تحديث حالة الميلستون لـ on_progress
            $milestone->update([
                'status' => 'on_progress',
                'is_open' => true,
            ]);

            // جلب كل الفرق اللي عندها الميلستون دي
            $teams = Team::where('academic_year_id', $academicYear->id)
                ->whereHas('milestones', function ($q) use ($milestone) {
                    $q->where('milestone_id', $milestone->id);
                })
                ->get();

            foreach ($teams as $team) {
                $members = TeamMembership::where('team_id', $team->id)
                    ->where('status', 'active')
                    ->with('user')
                    ->get();

                foreach ($members as $member) {
                    if ($member->user) {
                        DatabaseNotification::create([
                            'id' => (string) Str::uuid(),
                            'type' => 'milestone_started',
                            'notifiable_type' => 'App\\Models\\User',
                            'notifiable_id' => $member->user->id,
                            'academic_year_id' => $academicYear->id,
                            'data' => [
                                'type' => 'milestone_started',
                                'milestone_id' => $milestone->id,
                                'milestone_title' => $milestone->title,
                                'milestone_description' => $milestone->description,
                                'deadline' => $milestone->deadline->format('F d, Y'),
                                'message' => "Milestone '{$milestone->title}' has started. Deadline: {$milestone->deadline->format('F d, Y')}",
                                'icon' => 'play-circle',
                                'color' => 'green',
                                'created_at' => now(),
                            ],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }

            $this->info("Started milestone: {$milestone->title}");
        }

        // 2. الميليستونز اللي هتخلص بعد 3 أيام
        $endingSoonMilestones = Milestone::whereDate('deadline', '=', $today->copy()->addDays(3))
            ->where('status', 'on_progress')
            ->get();

        foreach ($endingSoonMilestones as $milestone) {
            // جلب كل الفرق اللي عندها الميلستون دي ولسه مسلمتش
            $teams = Team::where('academic_year_id', $academicYear->id)
                ->whereHas('milestones', function ($q) use ($milestone) {
                    $q->where('milestone_id', $milestone->id);
                })
                ->get();

            foreach ($teams as $team) {
                // نتأكد إن الفريق لسه مسلمش على الميلستون دي
                $hasSubmission = Submission::where('milestone_id', $milestone->id)
                    ->where('team_id', $team->id)
                    ->exists();

                if (!$hasSubmission) {
                    $members = TeamMembership::where('team_id', $team->id)
                        ->where('status', 'active')
                        ->with('user')
                        ->get();

                    foreach ($members as $member) {
                        if ($member->user) {
                            DatabaseNotification::create([
                                'id' => (string) Str::uuid(),
                                'type' => 'milestone_ending_soon',
                                'notifiable_type' => 'App\\Models\\User',
                                'notifiable_id' => $member->user->id,
                                'academic_year_id' => $academicYear->id,
                                'data' => [
                                    'type' => 'milestone_ending_soon',
                                    'milestone_id' => $milestone->id,
                                    'milestone_title' => $milestone->title,
                                    'deadline' => $milestone->deadline->format('F d, Y'),
                                    'days_left' => 3,
                                    'message' => "Reminder: Milestone '{$milestone->title}' is due in 3 days! Please submit your work before the deadline.",
                                    'icon' => 'clock',
                                    'color' => 'orange',
                                    'created_at' => now(),
                                ],
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            }

            $this->info("Ending soon milestone: {$milestone->title}");
        }

        $this->info('Milestone notifications sent successfully');
        return 0;
    }
}