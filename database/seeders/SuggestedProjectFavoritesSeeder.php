<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
    use App\Models\User;
use App\Models\SuggestedProject;
use Illuminate\Support\Facades\DB;
class SuggestedProjectFavoritesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */


public function run(): void
{
    $studentRole = Role::where('code', 'student')->first();

    $students = User::where('role_id', $studentRole->id)->get();
    $projects = SuggestedProject::all();

    foreach ($students as $student) {

        $randomProjects = $projects->random(rand(1,5));

        foreach ($randomProjects as $project) {

            DB::table('suggested_project_favorites')->insert([
                'student_user_id' => $student->id,
                'suggested_project_id' => $project->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
}
