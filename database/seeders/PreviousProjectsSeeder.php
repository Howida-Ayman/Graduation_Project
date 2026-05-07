<?php

namespace Database\Seeders;

use App\Models\PreviousProject;
use App\Models\Proposal;
use Illuminate\Database\Seeder;

class PreviousProjectsSeeder extends Seeder
{
    public function run(): void
    {
        $approvedProposals = Proposal::where('status', 'approved')
            ->with('team')
            ->get();

        foreach ($approvedProposals as $proposal) {

            if (
                !$proposal->team_id ||
                !$proposal->academic_year_id
            ) {
                continue;
            }

            PreviousProject::updateOrCreate(

                [
                    'proposal_id' => $proposal->id
                ],

                [

                    'academic_year_id' => $proposal->academic_year_id,

                    'team_id' => $proposal->team_id,

                    'final_score' => rand(70, 98),

                    'feedback' => fake()->paragraph(),

                    'graded_by' => collect([
                        'Dr Ahmed Hassan',
                        'Dr Mohamed Ali',
                        'Dr Sara Ibrahim',
                        'Dr Mahmoud Adel'
                    ])->random(),

                    'graded_at' => now()->subMonths(rand(2, 12)),

                    'archived_at' => now()->subMonths(rand(1, 10)),
                ]
            );
        }
    }
}