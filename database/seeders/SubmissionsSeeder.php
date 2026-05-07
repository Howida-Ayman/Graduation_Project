<?php

namespace Database\Seeders;

use App\Models\Milestone;
use App\Models\MilestoneCommitteeGrade;
use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Models\Team;
use App\Models\TeamMembership;
use App\Models\User;
use Illuminate\Database\Seeder;

class SubmissionsSeeder extends Seeder
{
    public function run(): void
    {
        $teams = Team::with('members.user')->get();

        $milestones = Milestone::where('is_active', true)->get();

        $doctors = User::where('role_id', 2)->get();

        foreach ($teams as $team) {

            foreach ($milestones as $milestone) {

                /*
                نخلي بعض التيمات رافعة وبعضها لا
                */

                if (rand(0, 100) < 20) {
                    continue;
                }

                $submitterMembership = TeamMembership::where('team_id', $team->id)
                    ->where('status', 'active')
                    ->inRandomOrder()
                    ->first();

                if (!$submitterMembership) {
                    continue;
                }

                $submission = Submission::create([

                    'team_id' => $team->id,

                    'milestone_id' => $milestone->id,

                    'submitted_by_user_id' => $submitterMembership->student_user_id,

                    'notes' => fake()->sentence(10),

                    'submitted_at' => now()->subDays(rand(1, 30)),
                ]);

                /*
                =========================
                FILES
                =========================
                */

                $filesCount = rand(1, 4);

                for ($i = 1; $i <= $filesCount; $i++) {

                    $hasFeedback = rand(0, 100) < 70;

                    $doctor = $doctors->random();

                    SubmissionFile::create([

                        'submission_id' => $submission->id,

                        'file_url' => "uploads/submissions/team{$team->id}_milestone{$milestone->id}_file{$i}.pdf",

                        'original_name' => "Milestone_File_{$i}.pdf",

                        'uploaded_at' => now()->subDays(rand(1, 20)),

                        'feedback' => $hasFeedback
                            ? fake()->paragraph()
                            : null,

                        'feedback_by_user_id' => $hasFeedback
                            ? $doctor->id
                            : null,

                        'feedback_at' => $hasFeedback
                            ? now()->subDays(rand(0, 10))
                            : null,
                    ]);
                

                }
            }
        }
    }
}