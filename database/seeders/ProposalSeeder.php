<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Proposal;
use App\Models\Team;
use App\Models\ProjectType;
use App\Models\AcademicYear;

class ProposalSeeder extends Seeder
{
    public function run(): void
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        $projectType = ProjectType::first();

        if (!$activeYear) {
            throw new \Exception('No active academic year found.');
        }

        if (!$projectType) {
            throw new \Exception('No project type found.');
        }

        $teams = Team::where('academic_year_id', $activeYear->id)->get();

        foreach ($teams as $team) {
            Proposal::updateOrCreate(
                [
                    'team_id' => $team->id,
                ],
                [
                    'academic_year_id' => $activeYear->id,
                    'submitted_by_user_id' => $team->leader_user_id,
                    'department_id' => $team->department_id,
                    'project_type_id' => $projectType->id,
                    'title' => match ($team->id) {
                        1 => 'AI Mental Health Support Assistant',
                        2 => 'Smart Campus Navigation System',
                        3 => 'Predictive Student Performance Platform',
                        4 => 'Multimedia Content Recommendation Engine',
                        default => 'Graduation Project ' . $team->id,
                    },
                    'description' => match ($team->id) {
                        1 => 'Mobile application that uses AI to support mental health through guided conversations and mood tracking.',
                        2 => 'Intelligent mobile and web system to help students navigate campus buildings, services, and schedules.',
                        3 => 'Analytics platform that predicts at-risk students using academic and behavioral indicators.',
                        4 => 'Recommendation engine for multimedia assets based on preferences, tags, and behavior.',
                        default => 'Graduation project proposal.',
                    },
                    'problem_statement' => 'Current solutions are fragmented and do not provide a smart, centralized, and user-friendly experience.',
                    'solution' => 'Build a scalable digital solution with a clear user flow, smart backend processing, and measurable output.',
                    'category' => 'Software Engineering',
                    'technologies' => match ($team->id) {
                        1 => 'Laravel, Flutter, Python, AI',
                        2 => 'Laravel, React, Maps API',
                        3 => 'Laravel, Vue, Machine Learning',
                        4 => 'Laravel, Flutter, Recommendation Engine',
                        default => 'Laravel, Flutter',
                    },
                    'attachment_file' => null,
                    'image_url' => null,
                    'status' => 'pending',
                    'decided_by_admin_id' => null,
                    'decided_at' => null,
                ]
            );
        }
    }
}