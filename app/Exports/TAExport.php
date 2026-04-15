<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TAExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return User::with('staffprofile.department')
            ->where('role_id', 3)
            ->get()
            ->map(function ($TA) {
                return [
                    'Name' => $TA->full_name,
                    'National ID' => $TA->national_id,
                    'Email' => $TA->email ?? "",
                    'Phone' => $TA->phone ?? "",
                    'Department' => $TA->staffprofile?->department?->name ?? "",
                    'Is Active' => $TA->is_active ? 'Yes' : 'No',
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Name',
            'National ID',
            'Email',
            'Phone',
            'Department',
            'Is Active'
        ];
    }
}