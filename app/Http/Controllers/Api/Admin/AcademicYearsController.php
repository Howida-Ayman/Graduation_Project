<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AcademicYearRequest;
use App\Models\AcademicYear;
use Illuminate\Http\Request;

class AcademicYearsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
      public function index()
    {
        $year=AcademicYear::all();
        return response()->json(
            ['message'=>'Academic Years retrieved successfully',
            'data'=>$year
        ],200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AcademicYearRequest $request)
    {  
        $year=AcademicYear::create([
            'code'=>$request->code
        ]);
        return response()->json(
            ['message'=>'Academic Years added successfully',
            'data'=>['id'=>$year->id,'code'=>$year->code]
        ],201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AcademicYearRequest $request, string $id)
    {
        try {
        $year=AcademicYear::findOrFail($id);
        $year->update([
            'code'=>$request->code]);
        return response()->json(
            ['message'=>'Academic Year updated successfully',
            'data'=>['id'=>$year->id,'code'=>$year->code,'updated_at'=>$year->updated_at]
        ],200);
            
        } catch (\Throwable $th) {
            return response()->json([
            'message' => 'id is invalid'
        ], 500);
        }
        
    }

   
}
