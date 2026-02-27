<?php

namespace Database\Seeders;


use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RuleItemsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
          DB::table('rule_items')->insert([

            // Project Type Requirements
            [
                'section' => 'project_type_requirements',
                'rules'   => 'Software Application',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'section' => 'project_type_requirements',
                'rules'   => 'AI/ML Project',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'section' => 'project_type_requirements',
                'rules'   => 'Hardware + Software Integration',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Idea Selection Criteria
            [
                'section' => 'idea_selection_criteria',
                'rules'   => 'The idea should be original and innovative.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'section' => 'idea_selection_criteria',
                'rules'   => 'The project must address a real-world problem.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'section' => 'idea_selection_criteria',
                'rules'   => 'Project ideas from previous years cannot be repeated.',
                'created_at' => now(),
                'updated_at' => now(),
            ],

        ]);
    
    }
}
