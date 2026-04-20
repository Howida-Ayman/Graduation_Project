<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\GraduationProject;
use App\Models\Proposal;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GraduationProjectsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $activeYear = AcademicYear::where('is_active', true)->first();

        if (!$activeYear) {
            throw new \Exception('No active academic year found.');
        }

        $proposals = Proposal::where('academic_year_id', $activeYear->id)->get();

        foreach ($proposals as $proposal) {
            GraduationProject::updateOrCreate(
                [
                    'team_id' => $proposal->team_id,
                ],
                [
                    'academic_year_id' => $activeYear->id,
                    'proposal_id' => $proposal->id,
                    'image_url' => $proposal->image_url,
                ]
            );
        }
    }
}
