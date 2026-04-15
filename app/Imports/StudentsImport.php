<?php

namespace App\Imports;

use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\StudentEnrollment;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Row;

class StudentsImport implements OnEachRow, WithHeadingRow, WithValidation
{
    private $departments;
    private $activeAcademicYear;

    public function __construct()
    {
        $this->departments = Department::pluck('id', 'name');
        $this->activeAcademicYear = AcademicYear::where('is_active', true)->first();
    }

    public function onRow(Row $row)
    {
        $data = $row->toArray();

        DB::transaction(function () use ($data) {
            $student = User::updateOrCreate(
                ['national_id' => $data['national_id']],
                [
                    'full_name' => $data['name'],
                    'email' => $data['email'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'password' => Hash::make($data['password'] ?? '123456'),
                    'role_id' => 4,
                    'is_active' => true,
                ]
            );

            if (!empty($data['department'])) {
                StudentProfile::updateOrCreate(
                    ['user_id' => $student->id],
                    [
                        'department_id' => $this->departments[$data['department']],
                        'gpa' => $data['gpa'] ?? null
                    ]
                );
            }

            if ($this->activeAcademicYear) {
                StudentEnrollment::updateOrCreate(
                    [
                        'student_user_id' => $student->id,
                        'academic_year_id' => $this->activeAcademicYear->id,
                    ],
                    [
                        'status' => 'active'
                    ]
                );
            }
        });
    }

    public function rules(): array
    {
      return [
            'national_id'=>'required|unique:users|digits:14',
            'name'=>'required|string',
            'email'=>'nullable|email|unique:users',
            'phone'=>'nullable|digits:11|unique:users',
            'department'=>'nullable|exists:departments,name',
            'gpa'=>'nullable|decimal:1,2'
        ];
    }
}