<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StudentsExport implements FromCollection,WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
       return User::with('studentprofile.department')->where('role_id',4)
        ->get()
        ->map(function($student)
        {
           return [
               'Name'=>$student->full_name,
               'National_id'=>$student->national_id,
               'Email'=>$student->email??"",
               'Department'=>$student->studentprofile?->department?->name??"",
               'GPA'=>$student->studentprofile->gpa??"",
               'Phone'=>$student->phone??"",
            ];
        });
    }
    public function headings(): array
    {
        return[
            'Name','National ID','Email','Department','GPA','Phone'
        ];
    }
}
