<?php

namespace Database\Seeders;

use App\Models\Milestone;
use App\Models\MilestoneRequirement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class MilestonesSeeder extends Seeder
{
    public function run(): void
    {
        $milestones = [
            [
                'phase_number' => 1,
                'title' => 'Phase 1: Project Kick-off & Discovery',
                'description' => 'Project initiation, team formation, and requirements gathering',
                'start_date' => '2025-09-01 00:00:00',
                'deadline' => '2025-10-15 23:59:59',
                'status' => 'completed',
                'is_open' => false,
                'requirements' => [
                    'Team formation and role assignment',
                    'Project proposal submission',
                    'Initial requirements gathering',
                    'Stakeholder identification',
                    'Project scope definition'
                ]
            ],
            [
                'phase_number' => 2,
                'title' => 'Phase 2: UI/UX Design & Prototyping',
                'description' => 'Design user interfaces, create wireframes, and build interactive prototypes',
                'start_date' => '2025-10-16 00:00:00',
                'deadline' => '2025-11-30 23:59:59',
                'status' => 'completed',
                'is_open' => false,
                'requirements' => [
                    'UI Wireframes for all main pages',
                    'Interactive prototype in Figma',
                    'User flow diagrams',
                    'Design system documentation',
                    'Usability testing report'
                ]
            ],
            [
                'phase_number' => 3,
                'title' => 'Phase 3: Frontend Development',
                'description' => 'Implement responsive user interfaces based on designs',
                'start_date' => '2025-12-01 00:00:00',
                'deadline' => '2026-01-15 23:59:59',
                'status' => 'on_progress',
                'is_open' => true,
                'requirements' => [
                    'Responsive dashboard layout',
                    'Authentication pages (login/register)',
                    'User profile management',
                    'API integration setup',
                    'Frontend testing suite'
                ]
            ],
            [
                'phase_number' => 4,
                'title' => 'Phase 4: Backend Development & API Integration',
                'description' => 'Develop RESTful APIs, database design, and business logic',
                'start_date' => '2026-01-16 00:00:00',
                'deadline' => '2026-03-15 23:59:59',
                'status' => 'on_progress',
                'is_open' => true,
                'requirements' => [
                    'Database schema design',
                    'RESTful API endpoints',
                    'Authentication & authorization',
                    'Data validation & error handling',
                    'API documentation with Swagger'
                ]
            ],
            [
                'phase_number' => 5,
                'title' => 'Phase 5: Testing & Quality Assurance',
                'description' => 'Comprehensive testing, bug fixing, and performance optimization',
                'start_date' => '2026-03-16 00:00:00',
                'deadline' => '2026-04-30 23:59:59',
                'status' => 'pending',
                'is_open' => false,
                'requirements' => [
                    'Unit testing (minimum 80% coverage)',
                    'Integration testing',
                    'User acceptance testing',
                    'Bug tracking report',
                    'Performance testing results'
                ]
            ],
            [
                'phase_number' => 6,
                'title' => 'Phase 6: Deployment & Final Presentation',
                'description' => 'Deploy to production, prepare documentation, and final presentation',
                'start_date' => '2026-05-01 00:00:00',
                'deadline' => '2026-06-15 23:59:59',
                'status' => 'pending',
                'is_open' => false,
                'requirements' => [
                    'Production deployment',
                    'User manual documentation',
                    'Technical documentation',
                    'Final presentation slides',
                    'Project demo video'
                ]
            ]
        ];
        
        // 3. إنشاء الـ milestones والمتطلبات
        foreach ($milestones as $milestoneData) {
            $requirements = $milestoneData['requirements'];
            unset($milestoneData['requirements']);
            
            $milestone = Milestone::create(array_merge($milestoneData));
            
            // إضافة المتطلبات
            foreach ($requirements as $index => $requirement) {
                MilestoneRequirement::create([
                    'milestone_id' => $milestone->id,
                    'requirement' => $requirement
                ]);
            }
            
            $this->command->info("Milestone {$milestone->phase_number} created: {$milestone->title}");
        }
        
        $this->command->info('All milestones seeded successfully!');
    
    }
}