<?php

namespace App\Http\Controllers\Api\Library;

use App\Http\Controllers\Controller;
use App\Http\Resources\LibraryProjectResource;
use App\Models\PreviousProject;
use Illuminate\Http\Request;
 use App\Models\Project;
use App\Models\SuggestedProject;

class LibraryController extends Controller
{
   
public function index(Request $request)
{
    $search = $request->search;
    $year = $request->year;
    $department = $request->department;
    $technology = $request->technology;

    // Suggested
    $suggested = SuggestedProject::query()
        ->with('department')
        ->withCount('favorites')
        ->where('is_active', true)
        ->when($search, fn($q) =>
            $q->where('title', 'like', "%$search%")
        )
        ->when($department, fn($q) =>
            $q->where('department_id', $department)
        )
        ->when($technology, fn($q) =>
            $q->where('recommended_tools', 'like', "%$technology%")
        )
        ->get();

    // Previous
    $previous = PreviousProject::query()
        ->with([
            'proposal.department',
            'proposal.team.academicYear'
        ])
        ->withCount('favorites')
        ->when($search, function ($q) use ($search) {
            $q->whereHas('proposal', fn($q2) =>
                $q2->where('title', 'like', "%$search%")
            );
        })
        ->when($department, function ($q) use ($department) {
            $q->whereHas('proposal', fn($q2) =>
                $q2->where('department_id', $department)
            );
        })
        ->when($year, function ($q) use ($year) {
            $q->whereHas('proposal.team', fn($q2) =>
                $q2->where('academic_year_id', $year)
            );
        })
        ->when($technology, function ($q) use ($technology) {
            $q->whereHas('proposal', fn($q2) =>
                $q2->where('technologies', 'like', "%$technology%")
            );
        })
        ->get();

    $collection = $suggested->merge($previous);

    return LibraryProjectResource::collection($collection);
}

public function suggested(Request $request)
{
    $search = $request->search;
    $department = $request->department;
    $technology = $request->technology;

    $projects = SuggestedProject::query()
        ->with('department')
        ->withCount('favorites')
        ->where('is_active', true)
        ->when($search, fn($q) =>
            $q->where('title', 'like', "%$search%")
        )
        ->when($department, fn($q) =>
            $q->where('department_id', $department)
        )
        ->when($technology, fn($q) =>
            $q->where('recommended_tools', 'like', "%$technology%")
        )
        ->latest()
        ->paginate(10);

    return LibraryProjectResource::collection($projects);
}

public function previous(Request $request)
{
    $search = $request->search;
    $department = $request->department;
    $technology = $request->technology;
    $year = $request->year;

    $projects = PreviousProject::query()
        ->with([
            'proposal.department',
            'proposal.team.academicYear'
        ])
        ->withCount('favorites')
        ->when($search, function ($q) use ($search) {
            $q->whereHas('proposal', fn($q2) =>
                $q2->where('title', 'like', "%$search%")
            );
        })
        ->when($department, function ($q) use ($department) {
            $q->whereHas('proposal', fn($q2) =>
                $q2->where('department_id', $department)
            );
        })
        ->when($technology, function ($q) use ($technology) {
            $q->whereHas('proposal', fn($q2) =>
                $q2->where('technologies', 'like', "%$technology%")
            );
        })
        ->when($year, function ($q) use ($year) {
            $q->whereHas('proposal.team', fn($q2) =>
                $q2->where('academic_year_id', $year)
            );
        })
        ->latest()
        ->paginate(10);

    return LibraryProjectResource::collection($projects);
}

public function favorites(Request $request)
{
    $user = $request->user();

    $suggested = $user->belongsToMany(
        SuggestedProject::class,
        'suggested_project_favorites',
        'student_user_id',
        'suggested_project_id'
    )
    ->with('department')
    ->withCount('favorites')
    ->get();

    $previous = $user->belongsToMany(
        PreviousProject::class,
        'previous_project_favorites',
        'student_user_id',
        'previous_project_id'
    )
    ->with([
        'proposal.department',
        'proposal.team.academicYear'
    ])
    ->withCount('favorites')
    ->get();

    $collection = $suggested->merge($previous);

    return LibraryProjectResource::collection($collection);
}



}
