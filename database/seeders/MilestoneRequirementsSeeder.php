<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MilestoneRequirementsSeeder extends Seeder
{
    public function run(): void
    {
        // هات milestones (ممكن تخص سنة معينة لو تحبي)
        $milestones = DB::table('milestones')->select('id', 'sort_order')->get();

        foreach ($milestones as $m) {
            $requirements = match ((int)$m->sort_order) {
                1 => [
                    'Project idea + problem statement',
                    'Scope and objectives',
                    'Initial plan (timeline + roles)',
                ],
                2 => [
                    'Wireframes',
                    'UI prototype (Figma or similar)',
                    'User flow / UX notes',
                ],
                3 => [
                    'Database schema / ERD',
                    'Core API endpoints implemented',
                    'Integration & testing evidence',
                ],
                4 => [
                    'Testing report',
                    'Deployment link / demo video',
                    'Final documentation',
                ],
                default => ['Requirement 1'],
            };

            foreach ($requirements as $req) {
                // updateOrInsert لتجنب التكرار
                DB::table('milestone_requirements')->updateOrInsert(
                    ['milestone_id' => $m->id, 'requirement' => $req],
                    [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }
}