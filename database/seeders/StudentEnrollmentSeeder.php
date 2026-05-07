<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\ProjectCourse;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Database\Seeder;

class StudentEnrollmentSeeder extends Seeder
{
    public function run(): void
    {
        $academicYear = AcademicYear::where('is_active', true)->first();

        $project1 = ProjectCourse::where('order', 1)->first();

        $project2 = ProjectCourse::where('order', 2)->first();

        /*
        =========================
        PROJECT 1 STUDENTS
        =========================
        */

        $project1Students = User::where('role_id', 4)
            ->where('email', 'like', 'p1student%')
            ->get();

        foreach ($project1Students as $student) {

            StudentEnrollment::updateOrCreate(
                [
                    'student_user_id' => $student->id,
                    'academic_year_id' => $academicYear->id,
                    'project_course_id' => $project1->id,
                ],
                [
                    'status' => 'in_progress',
                ]
            );
        }

        /*
        =========================
        PROJECT 2 STUDENTS
        =========================
        */

        $project2Students = User::where('role_id', 4)
            ->where('email', 'like', 'p2student%')
            ->get();

        foreach ($project2Students as $student) {

            /*
            Project I
            */

            StudentEnrollment::updateOrCreate(
                [
                    'student_user_id' => $student->id,
                    'academic_year_id' => $academicYear->id,
                    'project_course_id' => $project1->id,
                ],
                [
                    'status' => 'passed',
                ]
            );

            /*
            Project II
            */

            StudentEnrollment::updateOrCreate(
                [
                    'student_user_id' => $student->id,
                    'academic_year_id' => $academicYear->id,
                    'project_course_id' => $project2->id,
                ],
                [
                    'status' => 'in_progress',
                ]
            );
        }
    }
}