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

        DB::table('team_supervisors')->truncate();

        DB::table('team_supervisors')->insert([
            // Team 1 -> Doctor 5 + TA 10
            [
                'team_id' => 1,
                'supervisor_user_id' => 5,
                'supervisor_role' => 'doctor',
                'assigned_at' => Carbon::now()->subMonths(6),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'team_id' => 1,
                'supervisor_user_id' => 10,
                'supervisor_role' => 'ta',
                'assigned_at' => Carbon::now()->subMonths(6),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Team 2 -> نفس الدكتور ونفس المعيد
            [
                'team_id' => 2,
                'supervisor_user_id' => 5,
                'supervisor_role' => 'doctor',
                'assigned_at' => Carbon::now()->subMonths(6),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'team_id' => 2,
                'supervisor_user_id' => 10,
                'supervisor_role' => 'ta',
                'assigned_at' => Carbon::now()->subMonths(6),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Team 3 -> Doctor 6 + TA 11
            [
                'team_id' => 3,
                'supervisor_user_id' => 5,
                'supervisor_role' => 'doctor',
                'assigned_at' => Carbon::now()->subMonths(6),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'team_id' => 3,
                'supervisor_user_id' => 11,
                'supervisor_role' => 'ta',
                'assigned_at' => Carbon::now()->subMonths(6),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Team 4 -> نفس الدكتور ونفس المعيد
            [
                'team_id' => 4,
                'supervisor_user_id' => 5,
                'supervisor_role' => 'doctor',
                'assigned_at' => Carbon::now()->subMonths(6),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'team_id' => 4,
                'supervisor_user_id' => 10,
                'supervisor_role' => 'ta',
                'assigned_at' => Carbon::now()->subMonths(6),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}