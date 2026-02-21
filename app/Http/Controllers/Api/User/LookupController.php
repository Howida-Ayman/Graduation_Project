<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Department;
use Illuminate\Http\Request;

class LookupController extends Controller
{
    public function departments()
{
    $departments = Department::where('is_active', 1)
        ->select('id', 'name')
        ->get();

    return response()->json([
        'status' => true,
        'data' => $departments
    ]);
}
public function academicYears()
{
    $years = AcademicYear::select('id', 'code')
        ->get();

    return response()->json([
        'status' => true,
        'data' => $years
    ]);
}





}
