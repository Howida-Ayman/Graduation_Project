<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class DepartmentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $departments = [
            ['name' => 'IT','is_active'=>true  , 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'CS','is_active'=>true  ,'created_at' => $now, 'updated_at' => $now],
            ['name' => 'IS','is_active'=>true , 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'MM','is_active'=>true ,'created_at' => $now, 'updated_at' => $now],
        ];

        DB::table('departments')->upsert(
            $departments,
            ['name'],               // unique by
            ['updated_at']          // update columns
        );
    }
}
