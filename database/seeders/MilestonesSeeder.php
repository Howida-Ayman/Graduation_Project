<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Milestone;

class MilestonesSeeder extends Seeder
{
    public function run(): void
    {
        $milestones = [
            [
                'title' => 'Project Idea Submission',
                'description' => 'Submit initial project idea',
                'phase_number' => 1,
                'start_date' => now()->subDays(10),
                'deadline' => now()->subDays(5),
                'project_course_id' => 1,
            ],
            [
                'title' => 'Proposal Submission',
                'description' => 'Submit full proposal document',
                'phase_number' => 2,
                'start_date' => now()->subDay(),
                'deadline' => now()->addDays(10),
                'project_course_id' => 2,
            ],
            [
                'title' => 'Mid Evaluation',
                'description' => 'Mid-term evaluation',
                'phase_number' => 3,
                'start_date' => now()->addDays(21),
                'deadline' => now()->addDays(40),
                'project_course_id' => 1,
            ],
            [
                'title' => 'Final Submission',
                'description' => 'Final project delivery',
                'phase_number' => 4,
                'start_date' => now()->addDays(41),
                'deadline' => now()->addDays(60),
                'project_course_id' => 2,
            ],
        ];

        foreach ($milestones as $milestone) {
            Milestone::create([
                'title' => $milestone['title'],
                'description' => $milestone['description'],
                'phase_number' => $milestone['phase_number'],
                'start_date' => $milestone['start_date'],
                'deadline' => $milestone['deadline'],
                'project_course_id' => $milestone['project_course_id'],
                'max_score' => 5,
                'status' => 'pending',
                'is_open' => false,
                'is_active' => true,
            ]);
        }
    }
}