<?php

namespace Database\Seeders;

use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Models\Team;
use App\Models\Milestone;
use App\Models\TeamMembership;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SubmissionSeeder extends Seeder
{
    public function run(): void
    {
        $teams = Team::orderBy('id')->get();
        $milestones = Milestone::orderBy('phase_number')->get();

        if ($teams->count() < 4 || $milestones->count() < 2) {
            $this->command->error('Need at least 4 teams and 2 milestones before running SubmissionSeeder.');
            return;
        }

        $milestone1 = $milestones[0];
        $milestone2 = $milestones[1];

        // تنظيف قديم
        SubmissionFile::query()->delete();
        Submission::query()->delete();

        $teamLeader = function ($teamId) {
            return TeamMembership::where('team_id', $teamId)
                ->where('role_in_team', 'leader')
                ->where('status', 'active')
                ->value('student_user_id');
        };

        $createSubmission = function (
            int $teamId,
            int $milestoneId,
            int $submittedBy,
            Carbon $submittedAt,
            string $notes,
            array $files
        ) {
            $submission = Submission::create([
                'team_id' => $teamId,
                'milestone_id' => $milestoneId,
                'submitted_by_user_id' => $submittedBy,
                'notes' => $notes,
                'submitted_at' => $submittedAt,
            ]);

            foreach ($files as $file) {
                SubmissionFile::create([
                    'submission_id' => $submission->id,
                    'file_url' => $file['file_url'],
                    'original_name' => $file['original_name'],
                    'uploaded_at' => $submittedAt,
                    'feedback' => $file['feedback'] ?? null,
                ]);
            }

            return $submission;
        };

        // =========================
        // Milestone 1
        // كل التيمات سلمت
        // Team 2 و Team 4 متأخرين
        // =========================

        // Team 1 -> on time
        $submittedAtTeam1M1 = Carbon::parse($milestone1->deadline)->subDays(3);
        $createSubmission(
            1,
            $milestone1->id,
            $teamLeader(1),
            $submittedAtTeam1M1,
            'Phase 1 submission - team 1',
            [
                [
                    'file_url' => '/storage/submissions/team1/m1/problem_statement.pdf',
                    'original_name' => 'problem_statement.pdf',
                ],
                [
                    'file_url' => '/storage/submissions/team1/m1/project_scope.pdf',
                    'original_name' => 'project_scope.pdf',
                ],
            ]
        );

        DB::table('team_milestone_status')->updateOrInsert(
            ['team_id' => 1, 'milestone_id' => $milestone1->id],
            [
                'status' => 'on_track',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Team 2 -> late
        $submittedAtTeam2M1 = Carbon::parse($milestone1->deadline)->addDays(2);
        $createSubmission(
            2,
            $milestone1->id,
            $teamLeader(2),
            $submittedAtTeam2M1,
            'Phase 1 submission - team 2 (late)',
            [
                [
                    'file_url' => '/storage/submissions/team2/m1/idea_brief.pdf',
                    'original_name' => 'idea_brief.pdf',
                ],
                [
                    'file_url' => '/storage/submissions/team2/m1/objectives.docx',
                    'original_name' => 'objectives.docx',
                ],
            ]
        );

        DB::table('team_milestone_status')->updateOrInsert(
            ['team_id' => 2, 'milestone_id' => $milestone1->id],
            [
                'status' => 'delayed',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Team 3 -> on time
        $submittedAtTeam3M1 = Carbon::parse($milestone1->deadline)->subDay();
        $createSubmission(
            3,
            $milestone1->id,
            $teamLeader(3),
            $submittedAtTeam3M1,
            'Phase 1 submission - team 3',
            [
                [
                    'file_url' => '/storage/submissions/team3/m1/requirements.pdf',
                    'original_name' => 'requirements.pdf',
                ],
                [
                    'file_url' => '/storage/submissions/team3/m1/timeline.xlsx',
                    'original_name' => 'timeline.xlsx',
                ],
            ]
        );

        DB::table('team_milestone_status')->updateOrInsert(
            ['team_id' => 3, 'milestone_id' => $milestone1->id],
            [
                'status' => 'on_track',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Team 4 -> late
        $submittedAtTeam4M1 = Carbon::parse($milestone1->deadline)->addDays(4);
        $createSubmission(
            4,
            $milestone1->id,
            $teamLeader(4),
            $submittedAtTeam4M1,
            'Phase 1 submission - team 4 (late)',
            [
                [
                    'file_url' => '/storage/submissions/team4/m1/phase1_notes.pdf',
                    'original_name' => 'phase1_notes.pdf',
                ],
                [
                    'file_url' => '/storage/submissions/team4/m1/team_plan.docx',
                    'original_name' => 'team_plan.docx',
                ],
            ]
        );

        DB::table('team_milestone_status')->updateOrInsert(
            ['team_id' => 4, 'milestone_id' => $milestone1->id],
            [
                'status' => 'delayed',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // =========================
        // Milestone 2
        // Team 1 و Team 2 سلموا
        // Team 3 و Team 4 لسه ما سلموش
        // =========================

        $submittedAtTeam1M2 = Carbon::parse($milestone2->deadline)->subDays(2);
        $createSubmission(
            1,
            $milestone2->id,
            $teamLeader(1),
            $submittedAtTeam1M2,
            'Phase 2 submission - team 1',
            [
                [
                    'file_url' => '/storage/submissions/team1/m2/wireframes.pdf',
                    'original_name' => 'wireframes.pdf',
                ],
                [
                    'file_url' => '/storage/submissions/team1/m2/prototype.fig',
                    'original_name' => 'prototype.fig',
                ],
            ]
        );

        DB::table('team_milestone_status')->updateOrInsert(
            ['team_id' => 1, 'milestone_id' => $milestone2->id],
            [
                'status' => 'on_track',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $submittedAtTeam2M2 = Carbon::parse($milestone2->deadline)->subDay();
        $createSubmission(
            2,
            $milestone2->id,
            $teamLeader(2),
            $submittedAtTeam2M2,
            'Phase 2 submission - team 2',
            [
                [
                    'file_url' => '/storage/submissions/team2/m2/ui_flow.pdf',
                    'original_name' => 'ui_flow.pdf',
                ],
                [
                    'file_url' => '/storage/submissions/team2/m2/screens.zip',
                    'original_name' => 'screens.zip',
                ],
            ]
        );

        DB::table('team_milestone_status')->updateOrInsert(
            ['team_id' => 2, 'milestone_id' => $milestone2->id],
            [
                'status' => 'on_track',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Team 3 not submitted yet
        DB::table('team_milestone_status')->updateOrInsert(
            ['team_id' => 3, 'milestone_id' => $milestone2->id],
            [
                'status' => 'pending_submission',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Team 4 not submitted yet
        DB::table('team_milestone_status')->updateOrInsert(
            ['team_id' => 4, 'milestone_id' => $milestone2->id],
            [
                'status' => 'pending_submission',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $this->command->info('Submissions seeded successfully!');
    }
}