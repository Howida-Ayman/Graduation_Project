<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Team;
use App\Models\AcademicYear;

class TeamSeeder extends Seeder
{
    public function run(): void
    {
        $activeYear = AcademicYear::where('is_active', true)->first();

        if (!$activeYear) {
            throw new \Exception('No active academic year found.');
        }

        $teams = [
            [
                'id' => 1,
                'academic_year_id' => $activeYear->id,
                'department_id' => 1,
                'leader_user_id' => 15, // Mohamed Ali
            ],
            [
                'id' => 2,
                'academic_year_id' => $activeYear->id,
                'department_id' => 2,
                'leader_user_id' => 18, // Shahd Mostafa
            ],
            [
                'id' => 3,
                'academic_year_id' => $activeYear->id,
                'department_id' => 3,
                'leader_user_id' => 21, // Omar Hassan
            ],
            [
                'id' => 4,
                'academic_year_id' => $activeYear->id,
                'department_id' => 4,
                'leader_user_id' => 24, // Mariam Gamal
            ],
        ];

        foreach ($teams as $team) {
            Team::updateOrCreate(
                ['id' => $team['id']],
                $team
            );
        }
    }
}