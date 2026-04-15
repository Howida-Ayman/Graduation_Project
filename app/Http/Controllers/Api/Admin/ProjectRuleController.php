<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProjectRule;
use App\Models\RuleItem;
use Illuminate\Http\Request;

class ProjectRuleController extends Controller
{
    public function index()
    {
        $teamRules = ProjectRule::first();

        $projectTypeRequirements = RuleItem::select('id', 'section', 'rules')
            ->where('section', 'project_type_requirements')
            ->get();

        $ideaSelectionCriteria = RuleItem::select('id', 'section', 'rules')
            ->where('section', 'idea_selection_criteria')
            ->get();

        return response()->json([
            'message' => 'Data retrieved successfully',
            'team_formation_rules' => $teamRules,
            'graduation_project_rules' => [
                'project_type_requirements' => $projectTypeRequirements,
                'idea_selection_criteria' => $ideaSelectionCriteria
            ]
        ], 200);
    }

    public function updateTeamRules(Request $request)
    {
        $request->validate([
            'min_team_size' => 'required|integer|min:1|lt:max_team_size',
            'max_team_size' => 'required|integer|min:2|gt:min_team_size',
            'team_formation_deadline' => 'required|date|after_or_equal:today'
        ]);

        $teamRules = ProjectRule::updateOrCreate(
            ['id' => 1],
            [
                'min_team_size' => $request->min_team_size,
                'max_team_size' => $request->max_team_size,
                'team_formation_deadline' => $request->team_formation_deadline
            ]
        );

        return response()->json([
            'message' => 'Team formation rules saved successfully',
            'team_formation_rules' => $teamRules,
        ], 200);
    }

    public function storeRule(Request $request, $section)
    {
        $allowedSections = ['project_type_requirements', 'idea_selection_criteria'];

        if (!in_array($section, $allowedSections)) {
            return response()->json([
                'message' => 'Invalid section',
                'allowed_sections' => $allowedSections
            ], 400);
        }

        $request->validate([
            'rule' => 'required|string'
        ]);

        RuleItem::create([
            'section' => $section,
            'rules' => $request->rule
        ]);

        return response()->json([
            'message' => 'Rule added successfully',
            'data' => RuleItem::select('id', 'section', 'rules')
                ->where('section', $section)
                ->get()
        ], 201);
    }

    public function deleteRule($id)
    {
        try {
            $rule = RuleItem::findOrFail($id);
            $rule->delete();

            return response()->json([
                'message' => 'Rule deleted successfully',
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Rule not found',
            ], 404);
        }
    }
}