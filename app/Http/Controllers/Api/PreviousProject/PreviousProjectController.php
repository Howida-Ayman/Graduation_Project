<?php

namespace App\Http\Controllers\Api\PreviousProject;

use App\Http\Controllers\Controller;
use App\Models\PreviousProject;
use Illuminate\Http\Request;

class PreviousProjectController extends Controller
{
    public function index(Request $request)
{
    return PreviousProject::with([
        'proposal.team',
        'proposal.department'
    ])
    ->when($request->search, function ($q) use ($request) {
        $q->whereHas('proposal', function ($q2) use ($request) {
            $q2->where('title', 'like', "%{$request->search}%");
        });
    })
    ->paginate(6);
}
}
