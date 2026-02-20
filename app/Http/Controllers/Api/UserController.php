<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Imports\DoctorsImport;
use App\Models\StaffProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class UserController extends Controller
{
    public function all()
    {
        $doctors=User::with('staffprofile')->where('role_id',2)->get();
        return response()->json([
            'message'=>'doctors retrieved successfully',
            'data'=>$doctors
        ],200);
    }
    public function ImportDoctors(Request $request)
    {
        $request->validate(
           [ 'file'=>"required|mimes:xlsx,csv"]
        );
        Excel::import(new DoctorsImport,$request->file('file'));
        return response()->json([
            'message'=>'imported successfully',
        ],200);

    }
}
