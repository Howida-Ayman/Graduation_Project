<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Team;
use App\Models\User;
use App\Models\Department;
use App\Models\AcademicYear;

class TeamSeeder extends Seeder
{
    public function run(): void
    {
        $year = AcademicYear::first();
        $departments = Department::all();
        $students = User::where('role_id', 2)->get(); // الطلاب

        foreach ($departments as $department) {

            $leader = $students->random();

            Team::create([
                'academic_year_id' => $year->id,
                'department_id' => $department->id,
                'leader_user_id' => $leader->id,
            ]);
        }
    }
}