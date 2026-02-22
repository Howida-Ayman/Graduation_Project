<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
    use App\Models\User;
use App\Models\PreviousProject;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class PreviousProjectFavoritesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */


public function run(): void
{
    
    $studentRole = Role::where('code', 'student')->first();

    $students = User::where('role_id', $studentRole->id)->get();
    $projects = PreviousProject::all();

    foreach ($students as $student) {

        $randomProjects = $projects->random(rand(1,4));

        foreach ($randomProjects as $project) {

            DB::table('previous_project_favorites')->insert([
                'student_user_id' => $student->id,
                'previous_project_id' => $project->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
}
