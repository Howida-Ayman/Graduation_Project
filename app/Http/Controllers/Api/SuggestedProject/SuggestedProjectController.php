<?php

namespace App\Http\Controllers\Api\SuggestedProject;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuggestedProjectDetailResource;
use App\Models\SuggestedProject;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SuggestedProjectController extends Controller
{
    public function show($id)
    {
        $project = SuggestedProject::with('department')
            ->where('is_active', true)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new SuggestedProjectDetailResource($project)
        ], 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|unique:suggested_projects,title',
            'department_id' => [
                'required',
                Rule::exists('departments', 'id')->where(function ($query) {
                    $query->where('is_active', 1);
                }),
            ],
            'description' => 'required|string',
            'technologies' => 'nullable|string',
        ]);

        $sugProj = SuggestedProject::create([
            'title' => $request->title,
            'department_id' => $request->department_id,
            'description' => $request->description,
            'recommended_tools' => $request->technologies,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'data' => new SuggestedProjectDetailResource($sugProj->load('department'))
        ], 201);
    }

    public function update(Request $request, $id)
    {
        try {
            $sugProj = SuggestedProject::findOrFail($id);

            $request->validate([
                'title' => 'nullable|string|unique:suggested_projects,title,' . $sugProj->id,
                'department_id' => [
                    'nullable',
                    Rule::exists('departments', 'id')->where(function ($query) {
                        $query->where('is_active', 1);
                    }),
                ],
                'description' => 'nullable|string',
                'technologies' => 'nullable|string',
                'is_active' => 'nullable|boolean',
            ]);

            $sugProj->update([
                'title' => $request->filled('title') ? $request->title : $sugProj->title,
                'department_id' => $request->filled('department_id') ? $request->department_id : $sugProj->department_id,
                'description' => $request->filled('description') ? $request->description : $sugProj->description,
                'recommended_tools' => $request->filled('technologies') ? $request->technologies : $sugProj->recommended_tools,
                'is_active' => $request->filled('is_active') ? $request->is_active : $sugProj->is_active,
            ]);

            return response()->json([
                'success' => true,
                'data' => new SuggestedProjectDetailResource($sugProj->fresh()->load('department'))
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Suggested project not found'
            ], 404);

        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Something went wrong'
            ], 500);
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