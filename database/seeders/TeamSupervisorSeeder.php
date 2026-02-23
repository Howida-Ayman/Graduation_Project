<?php
// database/seeders/TeamSupervisorSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TeamSupervisorSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // ============================================
        // المشرفين للفريق 1 (CS)
        // ============================================
        // Doctor: Dr. Ahmed El-Nagar (5), TA: Ahmed Fayez (10)
        DB::table('team_supervisors')->insert([
            [
                'team_id' => 1,
                'supervisor_user_id' => 5, // Dr. Ahmed El-Nagar
                'supervisor_role' => 'doctor',
                'assigned_at' => Carbon::now()->subMonths(6),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'team_id' => 1,
                'supervisor_user_id' => 10, // Ahmed Fayez (TA)
                'supervisor_role' => 'ta',
                'assigned_at' => Carbon::now()->subMonths(6),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // ============================================
        // المشرفين للفريق 2 (IT)
        // ============================================
        // Doctor: Dr. Mona Hassan (6), TA: Mohamed Gayed (11)
        DB::table('team_supervisors')->insert([
            [
                'team_id' => 2,
                'supervisor_user_id' => 6, // Dr. Mona Hassan
                'supervisor_role' => 'doctor',
                'assigned_at' => Carbon::now()->subMonths(6),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'team_id' => 2,
                'supervisor_user_id' => 11, // Mohamed Gayed (TA)
                'supervisor_role' => 'ta',
                'assigned_at' => Carbon::now()->subMonths(6),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // ============================================
        // المشرفين للفريق 3 (IS)
        // ============================================
        // Doctor: Dr. Khaled Omar (7), TA: Sara Hassan (12)
        DB::table('team_supervisors')->insert([
            [
                'team_id' => 3,
                'supervisor_user_id' => 7, // Dr. Khaled Omar
                'supervisor_role' => 'doctor',
                'assigned_at' => Carbon::now()->subMonths(6),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'team_id' => 3,
                'supervisor_user_id' => 12, // Sara Hassan (TA)
                'supervisor_role' => 'ta',
                'assigned_at' => Carbon::now()->subMonths(6),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // ============================================
        // المشرفين للفريق 4 (MM)
        // ============================================
        // Doctor: Dr. Sarah Ahmed (8), TA: Nadia Ali (13)
        DB::table('team_supervisors')->insert([
            [
                'team_id' => 4,
                'supervisor_user_id' => 8, // Dr. Sarah Ahmed
                'supervisor_role' => 'doctor',
                'assigned_at' => Carbon::now()->subMonths(6),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'team_id' => 4,
                'supervisor_user_id' => 13, // Nadia Ali (TA)
                'supervisor_role' => 'ta',
                'assigned_at' => Carbon::now()->subMonths(6),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // ============================================
        // فريق إضافي - لو في فريق 5 مثلًا
        // ============================================
        // Doctor: Dr. Mahmoud Samir (9), TA: Omar Mahmoud (14)
        // DB::table('team_supervisors')->insert([
        //     [
        //         'team_id' => 5,
        //         'supervisor_user_id' => 9, // Dr. Mahmoud Samir
        //         'supervisor_role' => 'doctor',
        //         'assigned_at' => Carbon::now()->subMonths(6),
        //         'created_at' => $now,
        //         'updated_at' => $now,
        //     ],
        //     [
        //         'team_id' => 5,
        //         'supervisor_user_id' => 14, // Omar Mahmoud (TA)
        //         'supervisor_role' => 'ta',
        //         'assigned_at' => Carbon::now()->subMonths(6),
        //         'created_at' => $now,
        //         'updated_at' => $now,
        //     ],
        // ]);
    }
}