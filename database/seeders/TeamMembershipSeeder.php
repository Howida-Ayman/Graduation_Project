<?php
// database/seeders/TeamMembershipSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TeamMembershipSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $academic_year_id = 1; // 2024/2025

        // ============================================
        // الفريق 1 (CS) - 3 طلاب
        // ============================================
        // Student IDs: Mohamed Ali (15), Yara Tarek (16), Farida Khaled (17)
        DB::table('team_memberships')->insert([
            [
                'team_id' => 1,
                'academic_year_id' => $academic_year_id,
                'student_user_id' => 15, // Mohamed Ali
                'role_in_team' => 'leader',
                'status' => 'active',
                'joined_at' => Carbon::now()->subMonths(6),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'team_id' => 1,
                'academic_year_id' => $academic_year_id,
                'student_user_id' => 16, // Yara Tarek
                'role_in_team' => 'member',
                'status' => 'active',
                'joined_at' => Carbon::now()->subMonths(6),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'team_id' => 1,
                'academic_year_id' => $academic_year_id,
                'student_user_id' => 17, // Farida Khaled
                'role_in_team' => 'member',
                'status' => 'active',
                'joined_at' => Carbon::now()->subMonths(6),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // ============================================
        // الفريق 2 (IT) - 3 طلاب
        // ============================================
        // Student IDs: Shahd Mostafa (18), Ahmed Kamal (19), Rana Saleh (20)
        DB::table('team_memberships')->insert([
            [
                'team_id' => 2,
                'academic_year_id' => $academic_year_id,
                'student_user_id' => 18, // Shahd Mostafa
                'role_in_team' => 'leader',
                'status' => 'active',
                'joined_at' => Carbon::now()->subMonths(6),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'team_id' => 2,
                'academic_year_id' => $academic_year_id,
                'student_user_id' => 19, // Ahmed Kamal
                'role_in_team' => 'member',
                'status' => 'active',
                'joined_at' => Carbon::now()->subMonths(6),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'team_id' => 2,
                'academic_year_id' => $academic_year_id,
                'student_user_id' => 20, // Rana Saleh
                'role_in_team' => 'member',
                'status' => 'active',
                'joined_at' => Carbon::now()->subMonths(6),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // ============================================
        // الفريق 3 (IS) - 3 طلاب
        // ============================================
        // Student IDs: Omar Hassan (21), Nour Ahmed (22), Hossam Eldin (23)
        DB::table('team_memberships')->insert([
            [
                'team_id' => 3,
                'academic_year_id' => $academic_year_id,
                'student_user_id' => 21, // Omar Hassan
                'role_in_team' => 'leader',
                'status' => 'active',
                'joined_at' => Carbon::now()->subMonths(6),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'team_id' => 3,
                'academic_year_id' => $academic_year_id,
                'student_user_id' => 22, // Nour Ahmed
                'role_in_team' => 'member',
                'status' => 'active',
                'joined_at' => Carbon::now()->subMonths(6),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'team_id' => 3,
                'academic_year_id' => $academic_year_id,
                'student_user_id' => 23, // Hossam Eldin
                'role_in_team' => 'member',
                'status' => 'active',
                'joined_at' => Carbon::now()->subMonths(6),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // ============================================
        // الفريق 4 (MM) - 3 طلاب
        // ============================================
        // Student IDs: Mariam Gamal (24), Ali Youssef (25), Salma Ashraf (26)
        DB::table('team_memberships')->insert([
            [
                'team_id' => 4,
                'academic_year_id' => $academic_year_id,
                'student_user_id' => 24, // Mariam Gamal
                'role_in_team' => 'leader',
                'status' => 'active',
                'joined_at' => Carbon::now()->subMonths(6),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'team_id' => 4,
                'academic_year_id' => $academic_year_id,
                'student_user_id' => 25, // Ali Youssef
                'role_in_team' => 'member',
                'status' => 'active',
                'joined_at' => Carbon::now()->subMonths(6),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'team_id' => 4,
                'academic_year_id' => $academic_year_id,
                'student_user_id' => 26, // Salma Ashraf
                'role_in_team' => 'member',
                'status' => 'active',
                'joined_at' => Carbon::now()->subMonths(6),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

      
    }
}