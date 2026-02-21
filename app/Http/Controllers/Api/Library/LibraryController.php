<?php

namespace App\Http\Controllers\Api\Library;

use App\Http\Controllers\Controller;
use App\Models\PreviousProject;
use Illuminate\Http\Request;
 use App\Models\Project;
use App\Models\SuggestedProject;

class LibraryController extends Controller
{
   
public function index(Request $request)
{
    $suggestions = SuggestedProject::with('department')
        ->when($request->search, fn($q) =>
            $q->where('title', 'like', "%{$request->search}%")
        )
        ->when($request->department_id, fn($q) =>
            $q->where('department_id', $request->department_id)
        )
        ->take(6)
        ->get();

    $previous = PreviousProject::with([
        'proposal.team',
        'proposal.department'
    ])
    ->when($request->search, function ($q) use ($request) {
        $q->whereHas('proposal', function ($q2) use ($request) {
            $q2->where('title', 'like', "%{$request->search}%");
        });
    })
    ->take(6)
    ->get();

    return response()->json([
        'previous_projects' => $previous,
        'suggestions' => $suggestions
    ]);
}




}
