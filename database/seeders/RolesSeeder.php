<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $roles = [
            ['code' => 'Admin',   'created_at' => $now, 'updated_at' => $now],
            ['code' => 'Doctor',  'created_at' => $now, 'updated_at' => $now],
            ['code' => 'TA',      'created_at' => $now, 'updated_at' => $now],
            ['code' => 'Student', 'created_at' => $now, 'updated_at' => $now],
        ];

        DB::table('roles')->upsert(
            $roles,
            ['code'],               // unique by
            ['updated_at']          // update columns
        );
    }
}
