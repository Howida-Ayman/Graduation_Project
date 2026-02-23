<?php

namespace Database\Seeders;

use App\Models\SuggestedProject;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SuggestedProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
 



public function run(): void
{
    $adminRole = Role::where('code', 'admin')->first();

    if (!$adminRole) {
        return;
    }

    $admins = User::where('role_id', $adminRole->id)->get();

    if ($admins->isEmpty()) {
        return;
    }

    $departments = Department::all();

    foreach (range(1, 20) as $i) {
        SuggestedProject::create([
            'department_id' => $departments->random()->id,
            'title' => "Suggested Project $i",
            'description' => fake()->paragraph(),
            'recommended_tools' => fake()->randomElement([
                'Laravel, React',
                'Flutter, Firebase',
                'AI, Python',
                'Node.js, Vue'
            ]),
            'created_by_admin_id' => $admins->random()->id,
            'is_active' => true,
        ]);
    }
}




}
