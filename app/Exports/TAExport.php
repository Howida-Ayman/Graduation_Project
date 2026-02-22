<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TAExport implements FromCollection , WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return User::with('staffprofile')
        ->where('role_id',3)
        ->get()
        ->map(function($TA){
            return[
                'Name'=>$TA->full_name,
                'National Id'=>$TA->national_id,
                'Email'=>$TA->email??'-',
                'Phone'=>$TA->phone??'-',
                'Department'=>$TA->staffprofile->department->name??'-',

            ];

        });
    }
    public function headings(): array
    {
        return['Name','National ID','Email','Phone','Department'];
    }
}
