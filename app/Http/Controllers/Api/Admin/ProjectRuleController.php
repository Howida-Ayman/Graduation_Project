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
        $teamRules=ProjectRule::all();
        $project_type_requirements=RuleItem::select('id','section','rules')->where('section','project_type_requirements')->get();
        $idea_selection_criteria=RuleItem::select('id','section','rules')->where('section','idea_selection_criteria')->get();
        return response()->json([
            'message'=>'data retrieved successfully',
            'Team Formation Rules'=>$teamRules,
            'Graduation Project Rules'=>[
                'Project Type Requirements'=>$project_type_requirements,
                'Idea Selection Creiterias'=>$idea_selection_criteria
            ]
        ],200);
    }
    public function UpdateTeamRules(Request $request)
    {
       $request->validate([
        'min_team_size'=>'required|integer|min:1|lt:max_team_size',
        'max_team_size'=>'required|integer|min:2',
        'team_formation_deadline'=>'required|date|after_or_equal:today'
       ]);
       $teamRules=ProjectRule::updateOrCreate(
        ['id'=>1],[
        'min_team_size'=>$request->min_team_size,
        'max_team_size'=>$request->max_team_size,
        'team_formation_deadline'=>$request->team_formation_deadline
        ]);
        return response()->json([
            'message'=>'Team Formation Rules saved successfully',
            'Team Formation Rules'=>$teamRules,
        ],200);
    }
    public function StoreRules(Request $request,$section)
    {
        $request->validate([
            'rule'=>'required|string'
        ]);
        if($section=='project_type_requirements')
            {
                $rule=RuleItem::create([
                    'section'=>'project_type_requirements',
                    'rules'=>$request->rule
                ]);
                return response()->json([
                  'message'=>'Rules added successfully',
                  'Project Type Requirements'=>RuleItem::select('id','rules')->where('section','project_type_requirements')->get()
                ],201);
            }
        if($section=='idea_selection_criteria')
            {
                $rule=RuleItem::create([
                    'section'=>'idea_selection_criteria',
                    'rules'=>$request->rule
                ]);
                return response()->json([
                  'message'=>'Rules added successfully',
                  'Idea Selection Creiterias'=>RuleItem::select('id','rules')->where('section','idea_selection_criteria')->get()
                ],201);
            }
            else{
                return response()->json([
                  'message'=>'invalid section',
                ],500);
            }
    }
    public function deleteRule($id)
    {try {
        $rule=RuleItem::findOrFail($id)->delete();
        return response()->json([
            'message'=>'Rule deleted successfully',
            ],200);
    } catch (\Throwable $th) {
        return response()->json([
            'message'=>'something went wrong',
            ],500);
    }
        
    }
}
