<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $departments=Department::where('is_active',1)
        ->select('id','name')->get();
        return response()->json(
            ['message'=>'departments retrieved successfully',
            'data'=>$departments
        ],200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'=>'required|string|unique:departments,name'
        ]);
        $dept=Department::create([
            'name'=>$request->name
        ]);
        return response()->json(
            ['message'=>'department added successfully',
            'data'=>['id'=>$dept->id,'name'=>$dept->name]
        ],201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
        $dept=Department::findOrFail($id);
        $request->validate([
            'name'=>'nullable|string|unique:departments,name,'.$dept->id,
            'is_active'=>'nullable|boolean'
        ]);
        $dept->update([
            'name'=>$request->filled('name')?$request->name:$dept->name,
            'is_active'=>$request->filled('is_active')?$request->is_active:$dept->is_active]);
        return response()->json(
            ['message'=>'department updated successfully',
            'data'=>['id'=>$dept->id,'name'=>$dept->name,'is_active'=>$dept->is_active]
        ],200);
            
        } catch (\Throwable $th) {
            return response()->json([
            'message' => 'id is invalid'
        ], 500);
        }
        
    }

   
}
