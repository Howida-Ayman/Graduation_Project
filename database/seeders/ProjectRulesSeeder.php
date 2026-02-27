<?php

namespace Database\Seeders;

use App\Models\ProjectRule;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProjectRulesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         ProjectRule::updateOrCreate(
            [
                'min_team_size' => 4,
                'max_team_size' => 6,
                'team_formation_deadline' => now()->addWeeks(3)->toDateString(),
            ]
        );
    }
}
