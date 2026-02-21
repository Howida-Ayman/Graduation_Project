<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PreviousProject;
use App\Models\Proposal;

class PreviousProjectSeeder extends Seeder
{
    public function run(): void
    {
        $approvedProposals = Proposal::where('status', 'approved')->get();

        foreach ($approvedProposals as $proposal) {

            PreviousProject::create([
                'team_id' => $proposal->team_id,
                'proposal_id' => $proposal->id,
                'final_score' => rand(70, 100),
                'archived_at' => now(),
            ]);
        }
    }
}