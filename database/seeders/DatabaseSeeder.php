<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use StudentsProfileSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // RolesSeeder::class,
            // UsersSeeder::class,
            // DepartmentsSeeder::class,
            // AcademicYearSeeder::class,
            // StudentProfileSeeder::class,
            // ProjectTypeSeeder::class,
            // TeamSeeder::class,
            // ProposalSeeder::class,
            // PreviousProjectSeeder::class,
            SuggestedProjectSeeder::class,
            PreviousProjectFavoritesSeeder::class,
            SuggestedProjectFavoritesSeeder::class,
        
            
        ]
        );

    }
}
