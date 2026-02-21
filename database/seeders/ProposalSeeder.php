<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Proposal;
use App\Models\Team;
use App\Models\User;
use App\Models\ProjectType;

class ProposalSeeder extends Seeder
{
    public function run(): void
    {
        $teams = Team::all();
        $projectType = ProjectType::first();
        $admin = User::where('role_id', 1)->first(); // نفترض 1 = admin

        foreach ($teams as $team) {

            Proposal::create([
                'team_id' => $team->id,
                'submitted_by_user_id' => $team->leader_user_id,
                'department_id' => $team->department_id,
                'project_type_id' => $projectType->id,
                'title' => 'AI Graduation Project ' . $team->id,
                'description' => 'Smart AI based system.',
                'technologies' => 'Laravel, Flutter, AI',
                'status' => 'approved',
                'decided_by_admin_id' => $admin?->id,
                'decided_at' => now(),
            ]);
        }
    }
}