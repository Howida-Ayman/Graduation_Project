<?php

namespace App\Exports;

use App\Models\AcademicYear;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StudentsExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        $activeAcademicYear = AcademicYear::where('is_active', true)->first();

        if (!$activeAcademicYear) {
            return collect();
        }

        return User::with([
                'studentprofile.department',
                'enrollments' => function ($q) use ($activeAcademicYear) {
                    $q->where('academic_year_id', $activeAcademicYear->id)
                      ->where('status', 'active')
                      ->with('academicYear');
                }
            ])
            ->where('role_id', 4)
            ->whereHas('enrollments', function ($q) use ($activeAcademicYear) {
                $q->where('academic_year_id', $activeAcademicYear->id)
                  ->where('status', 'active');
            })
            ->get()
            ->map(function ($student) {
                $enrollment = $student->enrollments->first();

                return [
                    'Name' => $student->full_name,
                    'National ID' => $student->national_id,
                    'Email' => $student->email ?? "",
                    'Department' => $student->studentprofile?->department?->name ?? "",
                    'GPA' => $student->studentprofile?->gpa ?? "",
                    'Phone' => $student->phone ?? "",
                    'Is Active' => $student->is_active ? 'Yes' : 'No',
                    'Academic Year' => $enrollment?->academicYear?->code ?? "",
                    'Enrollment Status' => $enrollment?->status ?? "",
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Name',
            'National ID',
            'Email',
            'Department',
            'GPA',
            'Phone',
            'Is Active',
            'Academic Year',
            'Enrollment Status'
        ];
    }
}