<?php

namespace App\Http\Controllers\Api\SuggestedProject;

use App\Http\Controllers\Controller;
use App\Models\SuggestedProject;
use Illuminate\Http\Request;


class SuggestedProjectController extends Controller
{
    public function index(Request $request)
{
    return SuggestedProject::with('department')
        ->when($request->search, fn($q) =>
            $q->where('title', 'like', "%{$request->search}%")
        )
        ->when($request->department_id, fn($q) =>
            $q->where('department_id', $request->department_id)
        )
        ->paginate(6);
}
}
