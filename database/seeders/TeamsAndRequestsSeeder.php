<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\Request;
use App\Models\Team;
use App\Models\TeamMembership;
use App\Models\User;
use Illuminate\Database\Seeder;

class TeamsAndRequestsSeeder extends Seeder
{
    public function run(): void
    {
        $academicYear = AcademicYear::where('is_active', true)->first();
        $department = Department::first();

        $students = User::where('role_id', 4)
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        $chunks = $students->chunk(4);

        foreach ($chunks as $index => $chunk) {
            if ($chunk->isEmpty()) {
                continue;
            }

            $leader = $chunk->first();

            $team = Team::updateOrCreate(
                [
                    'academic_year_id' => $academicYear->id,
                    'leader_user_id' => $leader->id,
                ],
                [
                    'department_id' => $department?->id,
                ]
            );

            foreach ($chunk as $memberIndex => $student) {
                TeamMembership::updateOrCreate(
                    [
                        'team_id' => $team->id,
                        'student_user_id' => $student->id,
                        'academic_year_id' => $academicYear->id,
                    ],
                    [
                        'role_in_team' => $memberIndex === 0 ? 'leader' : 'member',
                        'status' => 'active',
                        'joined_at' => now()->subDays(rand(5, 20)),
                    ]
                );
            }
        }

        $teams = Team::where('academic_year_id', $academicYear->id)->get();

        $availableStudents = User::where('role_id', 4)
            ->where('is_active', true)
            ->whereDoesntHave('teamMemberships', function ($q) use ($academicYear) {
                $q->where('academic_year_id', $academicYear->id)
                  ->where('status', 'active');
            })
            ->get();

        foreach ($teams->take(5) as $team) {
            $target = $availableStudents->shift();

            if (!$target) {
                break;
            }

            Request::updateOrCreate(
                [
                    'academic_year_id' => $academicYear->id,
                    'from_user_id' => $team->leader_user_id,
                    'to_user_id' => $target->id,
                    'team_id' => $team->id,
                    'request_type' => 'team_form',
                ],
                [
                    'status' => 'pending',
                ]
            );
        }
    }
}