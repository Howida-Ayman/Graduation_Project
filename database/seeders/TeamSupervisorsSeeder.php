<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Team;
use App\Models\TeamSupervisor;
use App\Models\User;
use Illuminate\Database\Seeder;

class TeamSupervisorsSeeder extends Seeder
{
    public function run(): void
    {
        $academicYear = AcademicYear::where('is_active', true)->first();

        $doctors = User::where('role_id', 2)->where('is_active', true)->get();
        $tas = User::where('role_id', 3)->where('is_active', true)->get();

        $teams = Team::where('academic_year_id', $academicYear->id)->get();

        foreach ($teams as $index => $team) {
            $doctor = $doctors[$index % $doctors->count()] ?? null;
            $ta = $tas[$index % $tas->count()] ?? null;

            if ($doctor) {
                TeamSupervisor::updateOrCreate(
                    [
                        'team_id' => $team->id,
                        'supervisor_user_id' => $doctor->id,
                        'supervisor_role' => 'doctor',
                    ],
                    [
                        'assigned_at' => now()->subDays(10),
                        'ended_at' => null,
                    ]
                );
            }

            if ($ta) {
                TeamSupervisor::updateOrCreate(
                    [
                        'team_id' => $team->id,
                        'supervisor_user_id' => $ta->id,
                        'supervisor_role' => 'ta',
                    ],
                    [
                        'assigned_at' => now()->subDays(10),
                        'ended_at' => null,
                    ]
                );
            }
        }
    }
}