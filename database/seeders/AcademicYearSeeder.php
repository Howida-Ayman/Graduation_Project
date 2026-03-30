<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;


class AcademicYearSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
    $years = [
    '2024-2025',
    '2025-2026',
    '2026-2027',
];

foreach ($years as $index => $year) {
    AcademicYear::updateOrCreate(
        ['code' => $year],
        ['is_active' => $index === 0] // أول سنة = true
    );
}
    
}
}
