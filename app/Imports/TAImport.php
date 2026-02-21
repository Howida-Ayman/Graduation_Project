<?php

namespace App\Imports;

use App\Models\Department;
use App\Models\StaffProfile;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Row;

class TAImport implements OnEachRow ,WithHeadingRow,WithValidation
{
    private $department;
     public function __construct()
     {
        $this->department=Department::pluck('id','name');
     }
    public function onRow(Row $row)
    {

        DB::transaction(function() use($row)
        {
           $TA=User::updateOrCreate(
            ['national_id'=>$row['national_id']],
            [
                'full_name'=>$row['name'],
                'role_id'=>3,
                'email'=>$row['email']??null,
                'phone'=>$row['phone']??null,
                'password'=>Hash::make($row['password']??'123456')
            ]);
            StaffProfile::updateOrCreate(
                ['user_id'=>$TA->id],
                ['department_id'=>$this->department[$row['department']??null]]   
            );
        });
    }
    public function rules(): array
    {
        return[
            'national_id'=>'required|numeric|unique:users',
            'name'=>'required|string',
            'email'=>'nullable|email|unique:users',
            'phone'=>'nullable|numeric|unique:users',
            'department'=>'exists:Departments,name'
        ];
    }

   
}
