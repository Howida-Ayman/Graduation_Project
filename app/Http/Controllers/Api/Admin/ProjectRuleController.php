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
        $rules = ProjectRule::first();

        $milestoneCommitteeTotalScore = null;

        if ($rules) {
            $milestoneCommitteeTotalScore = 100 - (
                (float) $rules->supervisor_max_score +
                (float) $rules->defense_max_score
            );
        }

        $projectTypeRequirements = RuleItem::select('id', 'section', 'rules')
            ->where('section', 'project_type_requirements')
            ->get();

        $ideaSelectionCriteria = RuleItem::select('id', 'section', 'rules')
            ->where('section', 'idea_selection_criteria')
            ->get();

        return response()->json([
            'message' => 'Project rules retrieved successfully.',
            'team_formation_rules' => [
                'min_team_size' => $rules?->min_team_size,
                'max_team_size' => $rules?->max_team_size,
                'project1_team_formation_deadline' => $rules?->project1_team_formation_deadline,
            ],
            'grading_rules' => [
                'supervisor_max_score' => $rules?->supervisor_max_score,
                'defense_max_score' => $rules?->defense_max_score,
                'milestone_committee_total_score' => $milestoneCommitteeTotalScore,
                'passing_percentage' => $rules?->passing_percentage,
                'total_score' => 100,
            ],
            'graduation_project_rules' => [
                'project_type_requirements' => $projectTypeRequirements,
                'idea_selection_criteria' => $ideaSelectionCriteria,
            ],
        ], 200);
    }

    public function updateTeamRules(Request $request)
    {
        $request->validate([
            'min_team_size' => 'required|integer|min:1|lt:max_team_size',
            'max_team_size' => 'required|integer|min:2|gt:min_team_size',
            'project1_team_formation_deadline' => 'required|date|after_or_equal:today',
        ],);

        $rules = ProjectRule::updateOrCreate(
            ['id' => 1],
            [
                'min_team_size' => $request->min_team_size,
                'max_team_size' => $request->max_team_size,
                'project1_team_formation_deadline' => $request->project1_team_formation_deadline,
            ]
        );

        return response()->json([
            'message' => 'Team formation rules updated successfully.',
            'data' => [
                'min_team_size' => $rules->min_team_size,
                'max_team_size' => $rules->max_team_size,
                'project1_team_formation_deadline' => $rules->project1_team_formation_deadline,
            ],
        ], 200);
    }

    public function updateGradingRules(Request $request)
    {
        $request->validate([
            'supervisor_max_score' => 'required|numeric|min:0|max:50',
            'defense_max_score' => 'required|numeric|min:0|max:50',
            'passing_percentage' => 'required|numeric|min:0|max:100',
        ], 
        );

        $supervisorScore = (float) $request->supervisor_max_score;
        $defenseScore = (float) $request->defense_max_score;

        if (($supervisorScore + $defenseScore) >= 100) {
            return response()->json([
                'message' => 'Invalid score distribution. Supervisor and defense scores must leave remaining marks for milestone committee evaluation.',
            ], 422);
        }

        $rules = ProjectRule::updateOrCreate(
            ['id' => 1],
            [
                'supervisor_max_score' => $supervisorScore,
                'defense_max_score' => $defenseScore,
                'passing_percentage' => $request->passing_percentage,
            ]
        );

        $milestoneCommitteeTotalScore = 100 - ($supervisorScore + $defenseScore);

        return response()->json([
            'message' => 'Grading rules updated successfully.',
            'data' => [
                'supervisor_max_score' => $rules->supervisor_max_score,
                'defense_max_score' => $rules->defense_max_score,
                'milestone_committee_total_score' => $milestoneCommitteeTotalScore,
                'passing_percentage' => $rules->passing_percentage,
                'total_score' => 100,
            ],
        ], 200);
    }

    public function storeRule(Request $request, $section)
    {
        $allowedSections = ['project_type_requirements', 'idea_selection_criteria'];

        if (!in_array($section, $allowedSections)) {
            return response()->json([
                'message' => 'Invalid rule section.',
                'allowed_sections' => $allowedSections,
            ], 400);
        }

        $request->validate([
            'rule' => 'required|string|max:1000',
        ], [
            'rule.required' => 'Rule text is required.',
        ]);

        RuleItem::create([
            'section' => $section,
            'rules' => $request->rule,
        ]);

        return response()->json([
            'message' => 'Rule added successfully.',
            'data' => RuleItem::select('id', 'section', 'rules')
                ->where('section', $section)
                ->get(),
        ], 201);
    }

    public function deleteRule($id)
    {
        $rule = RuleItem::find($id);

        if (!$rule) {
            return response()->json([
                'message' => 'Rule not found.',
            ], 404);
        }

        $rule->delete();

        return response()->json([
            'message' => 'Rule deleted successfully.',
        ], 200);
    }
}