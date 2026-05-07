<?php

namespace Database\Seeders;

use App\Models\PreviousProject;
use App\Models\SuggestedProject;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FavoritesSeeder extends Seeder
{
    public function run(): void
    {
        $students = User::where('role_id', 4)->get();

        $previousProjects = PreviousProject::all();
        $suggestedProjects = SuggestedProject::all();

        foreach ($students as $student) {
            if ($previousProjects->isNotEmpty()) {
                $randomPrevious = $previousProjects->random(
                    rand(1, min(3, $previousProjects->count()))
                );

                foreach ($randomPrevious as $project) {
                    DB::table('previous_project_favorites')->updateOrInsert(
                        [
                            'student_user_id' => $student->id,
                            'previous_project_id' => $project->id,
                        ],
                        [
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            }

            if ($suggestedProjects->isNotEmpty()) {
                $randomSuggested = $suggestedProjects->random(
                    rand(1, min(3, $suggestedProjects->count()))
                );

                foreach ($randomSuggested as $project) {
                    DB::table('suggested_project_favorites')->updateOrInsert(
                        [
                            'student_user_id' => $student->id,
                            'suggested_project_id' => $project->id,
                        ],
                        [
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            }
        }
    }
}