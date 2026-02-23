<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exports\DoctorsExport;
use App\Http\Controllers\Controller;
use App\Imports\DoctorsImport;
use App\Models\StaffProfile;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;

class DoctorController extends Controller
{
      public function index(Request $request)
    {
        $perpage=$request->per_page??10;
        $doctors=User::with('staffprofile')->where('role_id',2)->paginate($perpage);
        return response()->json([
            'message'=>'doctors retrieved successfully',
            'data'=>$doctors
        ],200);
    }
    public function import(Request $request)
    {
        $request->validate(
           [ 'file'=>"required|mimes:xlsx,csv"]
        );
        Excel::import(new DoctorsImport,$request->file('file'));
        return response()->json([
            'message'=>'imported successfully',
        ],201);
    }
    public function export()
    {
        $fileName = 'doctors_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        Excel::store(new DoctorsExport,'Doctors/'.$fileName,'public');
        $url=asset('storage/Doctors/'.$fileName);
           return response()->json([
        'message' => 'Doctors exported successfully',
        'file_name' => $fileName,
        'download_url' => $url
        ],200);
    }
    public function store(Request $request)
    {
        $request->validate([
            'name'=>'required|string',
            'national_id'=>'required|unique:users|digits:14',
            'email'=>'nullable|email|unique:users',
            'phone'=>'nullable|digits:11|unique:users',
            'department_id'=>'nullable|exists:departments,id'
        ]);
        $user=DB::transaction(function() use($request)
        {
        $doctor=[
            'full_name'=>$request->name,
            'national_id'=>$request->national_id,
            'role_id'=>2,
            'email' => $request->filled('email') ? $request->email : null,
            'phone'=>$request->phone??null,
            'password'=>Hash::make('123456')
        ];
        $user= User::create($doctor);
        if($request->filled('department_id'))
            {
                StaffProfile::create([
                    'user_id'=>$user->id,
                    'department_id'=>$request->department_id
                ]);
            }
           return $user->load('staffprofile');
        });
        return response()->json([
            'message'=>'doctor added successfully',
            'doctor'=>$user,],201);
    }
   
}
