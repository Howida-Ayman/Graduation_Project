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
}