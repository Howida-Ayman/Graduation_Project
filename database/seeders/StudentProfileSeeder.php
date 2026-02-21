<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\StudentProfile;
use App\Models\User;
use App\Models\Department;

class StudentProfileSeeder extends Seeder
{
    public function run(): void
    {
        $students = User::where('role_id', 4)->get(); 

        $department = Department::first();

        foreach ($students as $student) {
            StudentProfile::firstOrCreate([
                'user_id' => $student->id,
                'department_id' => $department->id,
                'gpa' => rand(200, 400) / 100,
            ]);
        }
    }
}