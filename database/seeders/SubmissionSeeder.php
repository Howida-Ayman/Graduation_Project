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
        // 1. التأكد من وجود البيانات الأساسية
        $team = Team::first();
        $milestone = Milestone::where('status', 'on_progress')->first();
        $student = User::where('role_id', 4)->first();
        $doctor = User::where('role_id', 2)->first();

        if (!$team || !$milestone || !$student) {
            $this->command->error('Missing required data: team, milestone, or student not found!');
            return;
        }

        // 2. إنشاء Submission 1 (تم التقييم، مع feedback)
        $submission1 = Submission::create([
            'milestone_id' => $milestone->id,
            'team_id' => $team->id,
            'submitted_by_user_id' => $student->id,
            'notes' => 'This is our first submission for the milestone.',
            'submitted_at' => Carbon::now()->subDays(5),
            
        ]);

        // إضافة ملفات للـ submission الأول
        SubmissionFile::create([
            'submission_id' => $submission1->id,
            'file_url' => '/storage/submissions/design_system.pdf',
            'original_name' => 'design_system.pdf',
            'uploaded_at' => Carbon::now()->subDays(5),
        ]);

        // 3. إنشاء Submission 2 (تم التقييم، feedback تاني)
        $submission2 = Submission::create([
            'milestone_id' => $milestone->id,
            'team_id' => $team->id,
            'submitted_by_user_id' => $student->id,
            'notes' => 'Updated version after feedback.',
            'submitted_at' => Carbon::now()->subDays(3),
            
        ]);

        SubmissionFile::create([
            'submission_id' => $submission2->id,
            'file_url' => '/storage/submissions/backend_api.pdf',
            'original_name' => 'backend_api.pdf',
            'uploaded_at' => Carbon::now()->subDays(3),
        ]);

        // 4. إنشاء Submission 3 (لسه pending، مفيش تقييم)
        $submission3 = Submission::create([
            'milestone_id' => $milestone->id,
            'team_id' => $team->id,
            'submitted_by_user_id' => $student->id,
            'notes' => 'Final submission awaiting review.',
            'submitted_at' => Carbon::now(),
        ]);

        SubmissionFile::create([
            'submission_id' => $submission3->id,
            'file_url' => '/storage/submissions/final_report.pdf',
            'original_name' => 'final_report.pdf',
            'uploaded_at' => Carbon::now(),
        ]);

        $this->command->info('Submissions seeded successfully!');
    }
}