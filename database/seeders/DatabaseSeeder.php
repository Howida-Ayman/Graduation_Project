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
            RolesSeeder::class,
            AcademicYearSeeder::class,
            ProjectCourseSeeder::class,
            DepartmentsSeeder::class,
            ProjectTypeSeeder::class,
            ProjectRulesSeeder::class,
            RuleItemsSeeder::class,
            MilestonesSeeder::class,
            MilestoneRequirementsSeeder::class,
            StudentProfileSeeder::class,
            UsersSeeder::class,
            StudentEnrollmentSeeder::class,
            ProposalsAndGraduationProjectsSeeder::class,
            ProposalsSeeder::class,
            TeamsAndRequestsSeeder::class,
            TeamSupervisorsSeeder::class,
            SuggestedAndPreviousProjectsSeeder::class,
            PreviousProjectsSeeder::class,
            FavoritesSeeder::class,
            SubmissionsSeeder::class,
            CommitteesAndGradesSeeder::class,

        ]
        );

    }
}
