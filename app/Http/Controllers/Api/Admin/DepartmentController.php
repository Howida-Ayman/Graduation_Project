<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::select('id', 'name', 'is_active')->get();

        return response()->json([
            'message' => 'Departments retrieved successfully',
            'data' => $departments
        ], 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:departments,name'
        ]);

        $dept = Department::create([
            'name' => $request->name
        ]);

        return response()->json([
            'message' => 'Department added successfully',
            'data' => [
                'id' => $dept->id,
                'name' => $dept->name,
                'is_active' => $dept->is_active,
            ]
        ], 201);
    }

    public function update(Request $request, string $id)
    {
        try {
            $dept = Department::findOrFail($id);

            $request->validate([
                'name' => 'sometimes|string|unique:departments,name,' . $dept->id,
                'is_active' => 'sometimes|boolean'
            ]);

            $dept->update([
                'name' => $request->filled('name') ? $request->name : $dept->name,
                'is_active' => $request->filled('is_active') ? $request->is_active : $dept->is_active,
            ]);

            return response()->json([
                'message' => 'Department updated successfully',
                'data' => [
                    'id' => $dept->id,
                    'name' => $dept->name,
                    'is_active' => $dept->is_active,
                ]
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Department not found'
            ], 404);
        }
    }
}