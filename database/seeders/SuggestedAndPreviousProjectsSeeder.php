<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\PreviousProject;
use App\Models\Proposal;
use App\Models\SuggestedProject;
use Illuminate\Database\Seeder;

class SuggestedAndPreviousProjectsSeeder extends Seeder
{
    public function run(): void
    {
        $department = Department::first();

        /*
        |--------------------------------------------------------------------------
        | Suggested Projects
        |--------------------------------------------------------------------------
        */

        $ideas = [

            [
                'title' => 'AI Study Planner',
                'description' => 'A smart planner that recommends study schedules based on deadlines and performance.',
            ],

            [
                'title' => 'Smart Clinic Queue System',
                'description' => 'A web/mobile system for managing clinic queues and appointment priorities.',
            ],

            [
                'title' => 'Campus Lost & Found Platform',
                'description' => 'A platform for reporting and matching lost items on campus.',
            ],

            [
                'title' => 'Cyber Awareness Game',
                'description' => 'An interactive game teaching students phishing and password safety.',
            ],

            [
                'title' => 'AI CV Reviewer',
                'description' => 'A tool that reviews student CVs and suggests improvements.',
            ],

            [
                'title' => 'Smart Parking System',
                'description' => 'A smart parking management system using sensors and mobile tracking.',
            ],

            [
                'title' => 'E-Learning Analytics Dashboard',
                'description' => 'A dashboard for analyzing student performance and engagement in online learning.',
            ],

            [
                'title' => 'AI Chatbot for University Services',
                'description' => 'A chatbot that helps students navigate university services and FAQs.',
            ],
        ];

        foreach ($ideas as $idea) {

            SuggestedProject::updateOrCreate(

                [
                    'title' => $idea['title']
                ],

                [
                    'department_id' => $department?->id,

                    'description' => $idea['description'],

                    'is_active' => true,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Previous Projects
        |--------------------------------------------------------------------------
        */

       $approvedProposals = Proposal::with('team')
    ->where('status', 'approved')
    ->take(8)
    ->get();

foreach ($approvedProposals as $proposal) {
    if (!$proposal->team_id || !$proposal->academic_year_id) {
        continue;
    }

    PreviousProject::updateOrCreate(
        [
            'proposal_id' => $proposal->id,
        ],
        [
            'academic_year_id' => $proposal->academic_year_id,
            'team_id' => $proposal->team_id,
            'final_score' => rand(70, 98),
            'feedback' => fake()->sentence(12),
            'graded_by' => 'System Admin',
            'graded_at' => now()->subMonths(rand(2, 8)),
            'archived_at' => now()->subMonths(rand(1, 6)),
        ]
    );
}
}
}