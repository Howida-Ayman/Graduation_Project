<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\GraduationProject;
use App\Models\ProjectType;
use App\Models\Proposal;
use App\Models\Team;
use Illuminate\Database\Seeder;

class ProposalsAndGraduationProjectsSeeder extends Seeder
{
    public function run(): void
    {
        $academicYear = AcademicYear::where('is_active', true)->first();
        $projectType = ProjectType::first();

        $teams = Team::with('members.user')
            ->where('academic_year_id', $academicYear->id)
            ->get();

        $projectSamples = [
            [
                'title' => 'Smart Attendance Management System',
                'category' => 'Web Application',
                'description' => 'A system for managing student and staff attendance using digital tracking.',
                'problem_statement' => 'Manual attendance tracking is time-consuming and error-prone.',
                'solution' => 'A web-based attendance platform with reports and automated tracking.',
                'technologies' => 'Laravel, MySQL, Vue.js',
            ],
            [
                'title' => 'AI-Based Graduation Project Recommendation System',
                'category' => 'Artificial Intelligence',
                'description' => 'A recommendation engine that suggests project ideas based on student skills.',
                'problem_statement' => 'Students struggle to choose suitable graduation project ideas.',
                'solution' => 'An AI model recommends ideas based on interests, skills, and department tracks.',
                'technologies' => 'Python, Laravel, MySQL, Machine Learning',
            ],
            [
                'title' => 'Campus Helpdesk Ticketing System',
                'category' => 'Web Application',
                'description' => 'A support system for submitting and tracking university service requests.',
                'problem_statement' => 'Support requests are often lost or delayed due to manual communication.',
                'solution' => 'A ticketing workflow with priorities, status tracking, and notifications.',
                'technologies' => 'Laravel, MySQL, React',
            ],
            [
                'title' => 'Cyber Security Awareness Platform',
                'category' => 'Cyber Security',
                'description' => 'An educational platform for cybersecurity awareness and quizzes.',
                'problem_statement' => 'Students lack practical awareness of common cybersecurity threats.',
                'solution' => 'Interactive learning modules, quizzes, and awareness campaigns.',
                'technologies' => 'Laravel, MySQL, JavaScript',
            ],
            [
                'title' => 'Smart Library Reservation System',
                'category' => 'Information Systems',
                'description' => 'A platform to reserve books, study rooms, and library resources.',
                'problem_statement' => 'Library reservations are not efficiently managed.',
                'solution' => 'A digital reservation system with availability tracking.',
                'technologies' => 'Laravel, MySQL, Bootstrap',
            ],
        ];

        foreach ($teams as $index => $team) {
            $sample = $projectSamples[$index % count($projectSamples)];

            $departmentId = $team->department_id
                ?? Department::first()?->id;

            $proposal = Proposal::updateOrCreate(
                [
                    'team_id' => $team->id,
                ],
                [
                    'submitted_by_user_id' => $team->leader_user_id,
                    'academic_year_id' => $academicYear->id,
                    'department_id' => $departmentId,
                    'project_type_id' => $projectType?->id,

                    'title' => $sample['title'] . ' - Team ' . $team->id,
                    'description' => $sample['description'],
                    'problem_statement' => $sample['problem_statement'],
                    'solution' => $sample['solution'],
                    'category' => $sample['category'],
                    'technologies' => $sample['technologies'],

                    'attachment_file' => null,
                    'image_url' => null,

                    'status' => 'approved',
                ]
            );

            GraduationProject::updateOrCreate(
                [
                    'team_id' => $team->id,
                    'academic_year_id' => $academicYear->id,
                ],
                [
                    'proposal_id' => $proposal->id,
                    'image_url' => null,
                ]
            );
        }
    }
}