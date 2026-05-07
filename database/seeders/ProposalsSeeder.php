<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\ProjectType;
use App\Models\Proposal;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProposalsSeeder extends Seeder
{
    public function run(): void
    {
        $academicYear = AcademicYear::where('is_active', true)->first();

        $departments = Department::all();

        $projectTypes = ProjectType::all();

        $admin = User::where('role_id', 1)->first();

        $teams = Team::with([
                'members.user'
            ])
            ->get();

        $projects = [

            [
                'title' => 'AI Smart Attendance System',
                'category' => 'Artificial Intelligence',
                'description' => 'An AI-powered attendance tracking and analytics system.',
                'problem_statement' => 'Manual attendance systems consume time and contain errors.',
                'solution' => 'Using AI and face recognition to automate attendance tracking.',
                'technologies' => 'Laravel, Python, OpenCV, MySQL',
            ],

            [
                'title' => 'Cyber Security Awareness Platform',
                'category' => 'Cyber Security',
                'description' => 'A platform that trains students on cyber security threats.',
                'problem_statement' => 'Students lack practical cyber security awareness.',
                'solution' => 'Interactive training modules and attack simulations.',
                'technologies' => 'Laravel, Vue.js, MySQL',
            ],

            [
                'title' => 'Hospital Queue Management System',
                'category' => 'Information Systems',
                'description' => 'A smart hospital queue and appointment management platform.',
                'problem_statement' => 'Patients face delays and poor queue organization.',
                'solution' => 'Digital queue and booking workflows.',
                'technologies' => 'Laravel, Flutter, MySQL',
            ],

            [
                'title' => 'E-Learning Recommendation Engine',
                'category' => 'Machine Learning',
                'description' => 'A recommendation engine for personalized learning.',
                'problem_statement' => 'Students struggle to find suitable learning content.',
                'solution' => 'AI-based recommendation system.',
                'technologies' => 'Python, TensorFlow, Laravel',
            ],

            [
                'title' => 'Smart Parking Management',
                'category' => 'IoT',
                'description' => 'A parking management system using IoT sensors.',
                'problem_statement' => 'Parking congestion causes delays and inefficiency.',
                'solution' => 'Smart sensor-based parking monitoring.',
                'technologies' => 'ESP32, Laravel, MQTT',
            ],

        ];

        foreach ($teams as $index => $team) {

            $leader = $team->members
                ->where('role_in_team', 'leader')
                ->first();

            if (!$leader) {
                $leader = $team->members->first();
            }

            if (!$leader) {
                continue;
            }

            $sample = $projects[$index % count($projects)];

            $department = $departments->random();

            $projectType = $projectTypes->random();

            Proposal::updateOrCreate(

                [
                    'team_id' => $team->id
                ],

                [

                    'academic_year_id' => $academicYear->id,

                    'submitted_by_user_id' => $leader->student_user_id,

                    'department_id' => $department->id,

                    'project_type_id' => $projectType->id,

                    'title' => $sample['title'] . " Team {$team->id}",

                    'description' => $sample['description'],

                    'problem_statement' => $sample['problem_statement'],

                    'solution' => $sample['solution'],

                    'category' => $sample['category'],

                    'technologies' => $sample['technologies'],

                    'attachment_file' => "uploads/proposals/proposal_{$team->id}.pdf",

                    'image_url' => null,

                    'status' => collect([
                        'approved',
                        'approved',
                        'approved',
                        'pending',
                        'rejected'
                    ])->random(),

                    'decided_by_admin_id' => $admin?->id,

                    'decided_at' => now()->subDays(rand(1, 30)),

                    'admin_notes' => fake()->sentence(12),
                ]
            );
        }
    }
}