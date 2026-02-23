<?php

namespace App\Imports;

use App\Models\Department;
use App\Models\StaffProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Row;

class DoctorsImport implements OnEachRow ,WithHeadingRow,WithValidation
{
   private $deparments;
   
   public function __construct()
   {
    $this->deparments=Department::pluck('id','name');
   }
    public function onRow(Row $row)
    {
        DB::transaction(function() use($row)
        {
        $doctor = User::updateOrCreate(
        ['national_id'=>$row['national_id']],
        [
            'full_name'=>$row['name'],
            'email'=>$row['email']??null,
            'phone'=>$row['phone']??null,
            'password'=>Hash::make($row['password']??'123456'),
            'role_id'=>2 //doctor
        ]
        );
        if(!empty($row['department']))
            {
               
                StaffProfile::updateOrCreate(
                    ['user_id'=>$doctor->id],
                    [
                        'department_id'=>$this->deparments[$row['department']??null]
                    ]
                );
            }

        });

    }

    public function rules(): array
    {
        return[
            'national_id'=>'required|unique:users|digits:14',
            'name'=>'required|string',
            'email'=>'nullable|email|unique:users',
            'phone'=>'nullable|digits:11|unique:users',
            'department'=>'nullable|exists:departments,name'
        ];
    }
  
}
