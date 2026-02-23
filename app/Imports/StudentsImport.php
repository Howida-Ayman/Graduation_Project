<?php

namespace App\Imports;

use App\Models\Department;
use App\Models\StaffProfile;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Row;

class StudentsImport implements OnEachRow, WithHeadingRow,WithValidation
{
    /**
    * @param Collection $collection
    */
    private $department;
    private $gpa;
    public function __construct()
    {
        $this->department=Department::pluck('id','name');
    }
    public function onRow(Row $row)
    {
         DB::transaction(function() use($row)
         {
            $student=User::updateOrCreate(
                ['national_id'=>$row['national_id']],
            [
                'full_name'=>$row['name'],
                'email'=>$row['email']??null,
                'phone'=>$row['phone']??null,
                'password'=>Hash::make($row['password']??'123456'),
                'role_id'=>4
            ]);
            if(!empty($row['department']))
                {
                    StudentProfile::updateOrCreate(
                        ['user_id'=>$student->id],
                        [
                            'department_id'=>$this->department[$row['department']],
                            'gpa'=>$row['gpa']??null
                        ]
                    );
                    }
         });
    }
    public function rules(): array
    {
        return [
            'national_id'=>'required|unique:users|numeric',
            'name'=>'required|string',
            'email'=>'nullable|email|unique:users',
            'phone'=>'nullable|numeric|unique:users',
            'department'=>'nullable|exists:departments,name',
            'gpa'=>'nullable|decimal:1,2'
        ];
    }

}
