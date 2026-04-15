<?php

namespace App\Http\Controllers\Api\Library;

use App\Http\Controllers\Controller;
use App\Http\Resources\LibraryProjectResource;
use App\Models\PreviousProject;
use App\Models\SuggestedProject;
use Illuminate\Http\Request;

class LibraryController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'year', 'department', 'technology']);
        $perPage = $request->per_page ?? 10;

        $suggested = SuggestedProject::query()
            ->with('department')
            ->withCount('favorites')
            ->where('is_active', true)
            ->filter($filters)
            ->get();

        $previous = PreviousProject::query()
            ->with([
                'proposal.department',
                'proposal.team.academicYear',
            ])
            ->withCount('favorites')
            ->filter($filters)
            ->get();

        $collection = $suggested
            ->merge($previous)
            ->sortByDesc(function ($item) {
                return $item->created_at;
            })
            ->values();

        $page = (int) ($request->page ?? 1);
        $total = $collection->count();
        $items = $collection->forPage($page, $perPage)->values();

        return response()->json([
            'data' => LibraryProjectResource::collection($items),
            'meta' => [
                'current_page' => $page,
                'per_page' => (int) $perPage,
                'total' => $total,
                'last_page' => (int) ceil($total / $perPage),
            ]
        ], 200);
    }

    public function suggested(Request $request)
    {
        $filters = $request->only(['search', 'department', 'technology']);

        $projects = SuggestedProject::query()
            ->with('department')
            ->withCount('favorites')
            ->where('is_active', true)
            ->filter($filters)
            ->latest()
            ->paginate($request->per_page ?? 10);

        return LibraryProjectResource::collection($projects);
    }

    public function previous(Request $request)
    {
        $filters = $request->only(['search', 'department', 'technology', 'year']);

        $projects = PreviousProject::query()
            ->with([
                'proposal.department',
                'proposal.team.academicYear'
            ])
            ->withCount('favorites')
            ->filter($filters)
            ->latest()
            ->paginate($request->per_page ?? 10);

        return LibraryProjectResource::collection($projects);
    }

    public function favorites(Request $request)
    {
        $user = $request->user();
        $perPage = $request->per_page ?? 10;
        $page = (int) ($request->page ?? 1);

        $suggested = $user->favoriteSuggestedProjects()
            ->with('department')
            ->withCount('favorites')
            ->get();

        $previous = $user->favoritePreviousProjects()
            ->with([
                'proposal.department',
                'proposal.team.academicYear'
            ])
            ->withCount('favorites')
            ->get();

        $collection = $suggested
            ->merge($previous)
            ->sortByDesc(function ($item) {
                return $item->created_at;
            })
            ->values();

        $total = $collection->count();
        $items = $collection->forPage($page, $perPage)->values();

        return response()->json([
            'data' => LibraryProjectResource::collection($items),
            'meta' => [
                'current_page' => $page,
                'per_page' => (int) $perPage,
                'total' => $total,
                'last_page' => (int) ceil($total / $perPage),
            ]
        ], 200);
    }
}