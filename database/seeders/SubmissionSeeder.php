<?php

namespace Database\Seeders;

use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Models\Team;
use App\Models\Milestone;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class SubmissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. نجيب أول فريق موجود
        $team = Team::first();
        
        // 2. نجيب أول ميلستون موجود
        $milestone = Milestone::first();
        
        // 3. نجيب أول طالب موجود
        $student = User::where('role_id', 4)->first();

        if (!$team || !$milestone || !$student) {
            $this->command->error('Missing required data: team, milestone, or student not found!');
            return;
        }

        // 2. إنشاء Submission (بدون graded_by_user_id, score, feedback, graded_at)
        $submission = Submission::create([
            'milestone_id' => $milestone->id,
            'team_id' => $team->id,
            'submitted_by_user_id' => $student->id,
            'notes' => 'This is our first submission for the milestone.',
            'submitted_at' => Carbon::now()->subDays(5),
        ]);

        SubmissionFile::create([
            'submission_id' => $submission->id,
            'file_url' => '/storage/submissions/design_system.pdf',
            'original_name' => 'design_system.pdf',
            'uploaded_at' => Carbon::now()->subDays(5),
        ]);

        $this->command->info('Submission seeded successfully!');
    }
}