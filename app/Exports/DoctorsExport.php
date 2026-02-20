<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DoctorsExport implements FromCollection, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return User::with('staffprofile.department')
        ->where('role_id',2)
        ->get()
        ->map(function($doctor){
            return[
             'Name'=>$doctor->full_name,
             'National ID'=>$doctor->national_id,
             'Email'=>$doctor->email??'-',
             'Department'=>$doctor->staffprofile->department->name??'-',
             'phone'=>$doctor->phone??'-',
            ];
        });
    }
    public function headings(): array
    {
        return[
            'Name','National ID','Email','Department','phone'

        ];
    }
}
