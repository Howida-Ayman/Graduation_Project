<?php

namespace App\Imports;

use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\ProjectCourse;
use App\Models\StudentEnrollment;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Row;

class StudentsImport implements OnEachRow, WithHeadingRow, WithValidation
{
    private $departments;
    private $activeAcademicYear;
    private $projectCourse;
    private int $courseOrder;
    private int $processedRows = 0;

    public function __construct(int $courseOrder)
    {
        $this->departments = Department::pluck('id', 'name');
        $this->activeAcademicYear = AcademicYear::where('is_active', true)->first();
        $this->projectCourse = ProjectCourse::where('order', $courseOrder)->first();
        $this->courseOrder = $courseOrder;

        
        if (!$this->activeAcademicYear) {
            throw ValidationException::withMessages([
                'academic_year' => 'No active academic year found. Please activate a year before importing students.',
            ]);
        }

        if (!$this->projectCourse) {
            throw ValidationException::withMessages([
                'project_course' => 'Invalid project course. Please select a valid course.',
            ]);
        }
    }

    public function onRow(Row $row)
    {
        $data = $row->toArray();

        DB::transaction(function () use ($data) {

            //  Create / Update User
            $student = User::updateOrCreate(
                ['national_id' => $data['national_id']],
                [
                    'full_name' => $data['name'],
                    'email' => $data['email'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'password' => Hash::make($data['password'] ?? '123456'),
                    'role_id' => 4, // Student role
                    'is_active' => true,
                ]
            );

            //  Create / Update Profile
            if (!empty($data['department'])) {
                $departmentId = $this->departments[$data['department']] ?? null;

                if (!$departmentId) {
                    throw ValidationException::withMessages([
                        'department' => "Invalid department: {$data['department']}",
                    ]);
                }

StudentProfile::updateOrCreate(
    ['user_id' => $student->id],
    [
        'department_id' => $departmentId,
        'gpa' => $data['gpa'] ?? null
    ]
);
            }
            if ($this->courseOrder === 2) {
    $projectOne = ProjectCourse::where('order', 1)->first();

    $passedProjectOne = StudentEnrollment::where('student_user_id', $student->id)
        ->where('academic_year_id', $this->activeAcademicYear->id)
        ->where('project_course_id', $projectOne->id)
        ->where('status', 'passed')
        ->exists();

    if (!$passedProjectOne) {
        throw ValidationException::withMessages([
            'project_2_eligibility' => "Student {$student->full_name} cannot be enrolled in Capstone Project II because they have not passed Capstone Project I.",
        ]);
    }
}

            //  Enrollment Logic (core fix)
            StudentEnrollment::updateOrCreate(
                [
                    'student_user_id' => $student->id,
                    'academic_year_id' => $this->activeAcademicYear->id,
                    'project_course_id' => $this->projectCourse->id,
                ],
                [
                    'status' => 'in_progress'
                ]
            );

            $this->processedRows++;
        });
    }

    public function getProcessedRows(): int
    {
        return $this->processedRows;
    }

    public function rules(): array
    {
        return [
            'national_id' => 'required|digits:14',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|digits:11',
            'department' => 'nullable|exists:departments,name',
            'gpa' => 'nullable|numeric|min:0|max:4',
        ];
    }

   
    public function customValidationMessages()
    {
        return [
            'national_id.required' => 'National ID is required.',
            'national_id.digits' => 'National ID must be exactly 14 digits.',

            'name.required' => 'Student name is required.',

            'email.email' => 'Please provide a valid email address.',

            'phone.digits' => 'Phone number must be exactly 11 digits.',

            'department.exists' => 'Selected department does not exist.',

            'gpa.numeric' => 'GPA must be a valid number.',
            'gpa.max' => 'GPA cannot exceed 4.0.',
        ];
    }
}