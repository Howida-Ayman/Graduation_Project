<?php
// database/seeders/TeamMembershipSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\AcademicYear;

class TeamMembershipSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $activeYear = AcademicYear::where('is_active', true)->first();

        if (!$activeYear) {
            throw new \Exception('No active academic year found.');
        }

        $academic_year_id = $activeYear->id;

        DB::table('team_memberships')->truncate();

        $rows = [
            // Team 1
            [
                'team_id' => 1,
                'academic_year_id' => $academic_year_id,
                'student_user_id' => 15,
                'role_in_team' => 'leader',
                'status' => 'active',
                'joined_at' => Carbon::now()->subMonths(6),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'team_id' => 1,
                'academic_year_id' => $academic_year_id,
                'student_user_id' => 16,
                'role_in_team' => 'member',
                'status' => 'active',
                'joined_at' => Carbon::now()->subMonths(6),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'team_id' => 1,
                'academic_year_id' => $academic_year_id,
                'student_user_id' => 17,
                'role_in_team' => 'member',
                'status' => 'active',
                'joined_at' => Carbon::now()->subMonths(6),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Team 2
            [
                'team_id' => 2,
                'academic_year_id' => $academic_year_id,
                'student_user_id' => 18,
                'role_in_team' => 'leader',
                'status' => 'active',
                'joined_at' => Carbon::now()->subMonths(6),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'team_id' => 2,
                'academic_year_id' => $academic_year_id,
                'student_user_id' => 19,
                'role_in_team' => 'member',
                'status' => 'active',
                'joined_at' => Carbon::now()->subMonths(6),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'team_id' => 2,
                'academic_year_id' => $academic_year_id,
                'student_user_id' => 20,
                'role_in_team' => 'member',
                'status' => 'active',
                'joined_at' => Carbon::now()->subMonths(6),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Team 3
            [
                'team_id' => 3,
                'academic_year_id' => $academic_year_id,
                'student_user_id' => 21,
                'role_in_team' => 'leader',
                'status' => 'active',
                'joined_at' => Carbon::now()->subMonths(6),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'team_id' => 3,
                'academic_year_id' => $academic_year_id,
                'student_user_id' => 22,
                'role_in_team' => 'member',
                'status' => 'active',
                'joined_at' => Carbon::now()->subMonths(6),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'team_id' => 3,
                'academic_year_id' => $academic_year_id,
                'student_user_id' => 23,
                'role_in_team' => 'member',
                'status' => 'active',
                'joined_at' => Carbon::now()->subMonths(6),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Team 4
            [
                'team_id' => 4,
                'academic_year_id' => $academic_year_id,
                'student_user_id' => 24,
                'role_in_team' => 'leader',
                'status' => 'active',
                'joined_at' => Carbon::now()->subMonths(6),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'team_id' => 4,
                'academic_year_id' => $academic_year_id,
                'student_user_id' => 25,
                'role_in_team' => 'member',
                'status' => 'active',
                'joined_at' => Carbon::now()->subMonths(6),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'team_id' => 4,
                'academic_year_id' => $academic_year_id,
                'student_user_id' => 14, // Student Demo
                'role_in_team' => 'member',
                'status' => 'active',
                'joined_at' => Carbon::now()->subMonths(6),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('team_memberships')->insert($rows);
    }
}