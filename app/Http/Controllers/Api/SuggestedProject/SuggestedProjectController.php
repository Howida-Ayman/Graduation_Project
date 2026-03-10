<?php

namespace App\Http\Controllers\Api\SuggestedProject;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuggestedProjectDetailResource;
use App\Models\SuggestedProject;
use Illuminate\Http\Request;

class SuggestedProjectController extends Controller
{
    public function show($id)
    {
        $project = SuggestedProject::with([
            'department'
        ])
        ->withCount('favorites')
        ->findOrFail($id);

        return new SuggestedProjectDetailResource($project);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'=>'required|string|unique:suggested_projects,title',
            'department_id'=>'required|exists:departments,id',
            'description'=>"required|string",
            'technologies'=>'nullable'
        ]);
        $sug_proj=SuggestedProject::create([
        'title'=>$request->title,
        'department_id'=>$request->department_id,
        'description'=>$request->description,
        'recommended_tools'=>$request->technologies
    ]);
        return new SuggestedProjectDetailResource($sug_proj);
    }
        public function update(Request $request,$id)
    {
        try {
        $sug_proj=SuggestedProject::findOrFail($id);
        $request->validate([
            'title'=>'nullable|string|unique:suggested_projects,title,'.$sug_proj->id,
            'department_id'=>'nullable|exists:departments,id',
            'description'=>"nullable|string",
            'technologies'=>'nullable'
        ]);
        $sug_proj->update([
        'title'=>$request->filled('title')?$request->title:$sug_proj->title,
        'department_id'=>$request->filled(key: 'department_id')?$request->department_id:$sug_proj->department_id,
        'description'=>$request->filled('description')?$request->description:$sug_proj->description,
        'recommended_tools'=>$request->filled('technologies')?$request->technologies:$sug_proj->recommended_tools
    ]);
        return new SuggestedProjectDetailResource($sug_proj);
        } catch (\Throwable $th) {
            return response()->json([
                'message'=>'something went wrong'
            ],500);
        }
        
    }
    public function destroy($id)
    {
        $sug_proj=SuggestedProject::findOrFail($id)->delete();
        return response()->json([
                'message'=>'suggested project deleted successfuly'
            ],200);
    }

}