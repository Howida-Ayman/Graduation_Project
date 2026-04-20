<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MilestoneRequirementsSeeder extends Seeder
{
    public function run(): void
    {
        $milestones = DB::table('milestones')->select('id', 'phase_number')->orderBy('phase_number')->get();

        foreach ($milestones as $m) {
            $requirements = match ((int) $m->phase_number) {
                1 => [
                    'Approved project idea summary',
                    'Problem statement and target users',
                    'Scope, objectives, and expected output',
                    'Team roles and initial work plan',
                ],
                2 => [
                    'Wireframes for all main screens',
                    'UI prototype or clickable design',
                    'User flow and navigation map',
                    'Design notes and branding direction',
                ],
                3 => [
                    'Database schema / ERD',
                    'Core backend modules implemented',
                    'API endpoints documentation',
                    'Integration evidence between frontend and backend',
                ],
                4 => [
                    'Testing report and bug fixes summary',
                    'Deployment link or demo build',
                    'User manual and technical documentation',
                    'Presentation deck and final demo preparation',
                ],
                default => [
                    'Milestone deliverable 1',
                    'Milestone deliverable 2',
                ],
            };

            foreach ($requirements as $req) {
                DB::table('milestone_requirements')->updateOrInsert(
                    [
                        'milestone_id' => $m->id,
                        'requirement' => $req,
                    ],
                    [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }
}