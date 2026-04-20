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
        $admin = User::where('role_id', 1)->first(); // نفترض 1 = admin\
        $year=AcademicYear::where('is_active',true)->first();

        foreach ($teams as $team) {

            Proposal::create([
                'team_id' => $team->id,
                'submitted_by_user_id' => $team->leader_user_id,
                'department_id' => $team->department_id,
                'project_type_id' => $projectType->id,
                'title' => 'AI Graduation Project ' . $team->id,
                'description' => 'Smart AI based system.',
                'problem_statement' => 'How to leverage AI to solve real-world problems?',
                'solution' => 'By developing an intelligent system that can analyze data and provide actionable insights.',
                'technologies' => 'Laravel, Flutter, AI',
                'academic_year_id' => $year->id,
                'status' => 'approved',
                'decided_by_admin_id' => $admin?->id,
                'decided_at' => now(),
            ]);
        }
    }
}