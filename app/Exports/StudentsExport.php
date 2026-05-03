<?php

namespace App\Exports;

use App\Models\AcademicYear;
use App\Models\ProjectCourse;
use App\Models\StudentEnrollment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StudentsExport implements FromCollection, WithHeadings
{
    private $course;

    public function __construct($course)
    {
        $this->course = $course;
    }

    public function collection()
    {
        $activeAcademicYear = AcademicYear::where('is_active', true)->first();

        $projectCourse = ProjectCourse::where('order', $this->course)->first();

        if (!$activeAcademicYear || !$projectCourse) {
            return collect([]);
        }

        return StudentEnrollment::with([
        'student.studentprofile.department',
        'academicYear',
        'projectCourse'
         ])
        ->where('academic_year_id', $activeAcademicYear->id)
        ->where('project_course_id', $projectCourse->id)
        ->whereHas('student', function ($q) {
         $q->where('is_active', true);
        })
            ->get()
            ->map(function ($enrollment) {

                $student = $enrollment->student;

                return [
                    'student_id' => $student?->id,
                    'full_name' => $student?->full_name,
                    'national_id' => $student?->national_id,
                    'email' => $student?->email,
                    'phone' => $student?->phone,
                    'department' => $student?->studentprofile?->department?->name,
                    'gpa' => $student?->studentprofile?->gpa,
                    'academic_year' => $enrollment->academicYear?->code,
                    'status' => $enrollment->status,
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Student ID',
            'Full Name',
            'National ID',
            'Email',
            'Phone',
            'Department',
            'GPA',
            'Academic Year',
            'Status',
        ];
    }
}