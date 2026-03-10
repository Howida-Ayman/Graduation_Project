<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class MilestonesSeeder extends Seeder
{
    public function run(): void
    {
        // اختاري السنة اللي هتربطي عليها (لازم تكون موجودة في academic_years)
        $academicYearId = DB::table('academic_years')->where('code', '2024-2025')->value('id')
            ?? DB::table('academic_years')->min('id');


        // مثال تواريخ (عدّليهم براحتك)
        $m1Start = Carbon::parse('2025-11-01 00:00:00');
        $m1End   = Carbon::parse('2025-11-30 23:59:59');

        $m2Start = Carbon::parse('2025-12-01 00:00:00');
        $m2End   = Carbon::parse('2025-12-30 23:59:59');

        $m3Start = Carbon::parse('2026-01-01 00:00:00');
        $m3End   = Carbon::parse('2026-01-30 23:59:59');

        $m4Start = Carbon::parse('2026-02-01 00:00:00');
        $m4End   = Carbon::parse('2026-02-28 23:59:59');

        // Upsert عشان لو اتعمل seed تاني ما يكررش
        DB::table('milestones')->updateOrInsert(
            ['academic_year_id' => $academicYearId, 'sort_order' => 1],
            [
                'title' => 'Project Kick-off & Discovery',
                'description' => 'Initial project idea, scope, and plan.',
                'start_date' => $m1Start,
                'deadline' => $m1End,
                'status' => 'completed',
                'is_open' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('milestones')->updateOrInsert(
            ['academic_year_id' => $academicYearId, 'sort_order' => 2],
            [
                'title' => 'User Interface Design & Prototyping',
                'description' => 'UI/UX design and prototype deliverables.',
                'start_date' => $m2Start,
                'deadline' => $m2End,
                'status' => 'on_progress',
                'is_open' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('milestones')->updateOrInsert(
            ['academic_year_id' => $academicYearId, 'sort_order' => 3],
            [
                'title' => 'Backend Development & API Integration',
                'description' => 'Backend features, APIs, and integration.',
                'start_date' => $m3Start,
                'deadline' => $m3End,
                'status' => 'on_progress',
                'is_open' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('milestones')->updateOrInsert(
            ['academic_year_id' => $academicYearId, 'sort_order' => 4],
            [
                'title' => 'Testing, Deployment & Documentation',
                'description' => 'Final testing, deployment and documentation.',
                'start_date' => $m4Start,
                'deadline' => $m4End,
                'status' => 'pending',
                'is_open' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}