<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProjectType;

class ProjectTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'name' => 'System',
                'description' => 'Desktop or enterprise systems',
            ],
            [
                'name' => 'Website',
                'description' => 'Web-based applications',
            ],
            [
                'name' => 'Mobile App',
                'description' => 'Android or iOS applications',
            ],
            [
                'name' => 'Research',
                'description' => 'Academic or research-based projects',
            ],
        ];

        foreach ($types as $type) {
            ProjectType::updateOrCreate(
                ['name' => $type['name']],
                $type
            );
        }
    }
}