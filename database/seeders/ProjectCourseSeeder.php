<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProjectCourseSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('project_courses')->updateOrInsert(
            ['name' => 'Capstone Project I'],
            ['order' => 1, 'updated_at' => now(), 'created_at' => now()]
        );

        DB::table('project_courses')->updateOrInsert(
            ['name' => 'Capstone Project II'],
            ['order' => 2, 'updated_at' => now(), 'created_at' => now()]
        );
    }
}