<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AcademicYearRequest;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AcademicYearsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
      public function index()
    {
        $year=AcademicYear::select('id','code','is_active')->get();
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
        $year=DB::transaction(function() use ($request)
        {
            // 1. أول شيء: نجيب السنة النشطة الحالية ونجعلها غير نشطة
        AcademicYear::where('is_active', true)->update(['is_active' => false]);
        
        // 2. بعد كده نضيف السنة الجديدة كنشطة
            $year=AcademicYear::create([
                'code'=>$request->code,
                'is_active'=>true
            ]);
            return $year;
            });
            return response()->json(
                ['message'=>'Academic Years added successfully',
                'data'=>['id'=>$year->id,'code'=>$year->code,'is_active'=>$year->is_active]
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
            'data'=>['id'=>$year->id,'code'=>$year->code,'is_active'=>$year->is_active,'updated_at'=>$year->updated_at]
        ],200);
            
        } catch (\Throwable $th) {
            return response()->json([
            'message' => 'Academic Year not found'
        ], 404);
        }
    }
    public function setActive(string $id)
{
    try {
        DB::transaction(function() use ($id) {
            // نشيل النشاط من الكل
            AcademicYear::where('is_active', true)->update(['is_active' => false]);
            
            // نشغل السنة المطلوبة
            $year = AcademicYear::findOrFail($id);
            $year->update(['is_active' => true]);
        });
        
        return response()->json([
            'message' => 'Academic Year set as active successfully'
        ], 200);
            
    } catch (\Throwable $th) {
        return response()->json([
            'message' => 'Academic Year not found'
        ], 404);
    }
}
   
}
