<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AcademicYearRequest;
use App\Models\AcademicYear;
use App\Models\StudentEnrollment;
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
            $previousActiveYear = AcademicYear::where('is_active', true)->first();

// اقفل أي سنة حالية
AcademicYear::where('is_active', true)->update([
    'is_active' => false
]);

// فعّل/أنشئ السنة الجديدة
$year = AcademicYear::create([
    'code' => $request->code,
    'is_active' => true,
]);

// لو كان فيه سنة قديمة فعالة، هات الطلبة الساقطين منها
if ($previousActiveYear) {
    $failedStudents = StudentEnrollment::where('academic_year_id', $previousActiveYear->id)
        ->where('status', 'failed')
        ->get();

    foreach ($failedStudents as $enrollment) {
        StudentEnrollment::updateOrCreate(
            [
                'student_user_id' => $enrollment->student_user_id,
                'academic_year_id' => $year->id,
            ],
            [
                'status' => 'active'
            ]
        );
    }
}
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
        $year = DB::transaction(function () use ($id) {
            // السنة اللي كانت مفعلة قبل التغيير
            $previousActiveYear = AcademicYear::where('is_active', true)->first();

            // السنة المطلوب تفعيلها
            $year = AcademicYear::findOrFail($id);

            // لو هي بالفعل مفعلة، رجعها كما هي
            if ($year->is_active) {
                return $year;
            }

            // اقفل أي سنة مفعلة
            AcademicYear::where('is_active', true)->update([
                'is_active' => false
            ]);

            // فعل السنة المطلوبة
            $year->update([
                'is_active' => true
            ]);

            // لو كان فيه سنة قديمة مفعلة، انقل الطلبة الساقطين منها للسنة الجديدة
            if ($previousActiveYear) {
                $failedStudents = \App\Models\StudentEnrollment::where('academic_year_id', $previousActiveYear->id)
                    ->where('status', 'failed')
                    ->get();

                foreach ($failedStudents as $enrollment) {
                    \App\Models\StudentEnrollment::updateOrCreate(
                        [
                            'student_user_id' => $enrollment->student_user_id,
                            'academic_year_id' => $year->id,
                        ],
                        [
                            'status' => 'active'
                        ]
                    );
                }
            }

            return $year->fresh();
        });

        return response()->json([
            'message' => 'Academic Year set as active successfully',
            'data' => [
                'id' => $year->id,
                'code' => $year->code,
                'is_active' => $year->is_active,
            ]
        ], 200);

    } catch (\Throwable $th) {
        return response()->json([
            'message' => 'Academic Year not found'
        ], 404);
    }
}
   
}
