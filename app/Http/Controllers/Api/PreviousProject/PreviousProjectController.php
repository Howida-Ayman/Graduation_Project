<?php
// app/Http/Controllers/Api/PreviousProject/PreviousProjectController.php

namespace App\Http\Controllers\Api\PreviousProject;

use App\Http\Controllers\Controller;
use App\Http\Resources\PreviousProjectDetailResource;
use App\Models\PreviousProject;
use Illuminate\Http\Request;

class PreviousProjectController extends Controller
{
    public function show($id)
    {
        try {
            $project = PreviousProject::with([
                'proposal.department',
                'proposal.team.academicYear',
                'proposal.team.members',
                'proposal.team.supervisors',
                'proposal.projectType'
            ])
            ->withCount('favorites')
            ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => new PreviousProjectDetailResource($project)
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Project not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }
}