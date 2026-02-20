<?php

namespace App\Http\Controllers\Api;

use App\Exports\DoctorsExport;
use App\Http\Controllers\Controller;
use App\Imports\DoctorsImport;
use App\Models\StaffProfile;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class UserController extends Controller
{
    public function all(Request $request)
    {
        $perpage=$request->per_page??10;
        $doctors=User::with('staffprofile')->where('role_id',2)->paginate($perpage);
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
    public function ExportDoctors()
    {
        $fileName = 'doctors_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        Excel::store(new DoctorsExport,$fileName,'public');
        $url=asset('storage/'.$fileName);
           return response()->json([
        'message' => 'Doctors exported successfully',
        'file_name' => $fileName,
        'download_url' => $url
        ],200);
    }
}
