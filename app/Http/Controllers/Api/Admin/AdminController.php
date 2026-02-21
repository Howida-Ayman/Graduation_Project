<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exports\DoctorsExport;
use App\Http\Controllers\Controller;
use App\Imports\DoctorsImport;
use App\Models\StaffProfile;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;

class AdminController extends Controller
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
    public function storeDoctor(Request $request)
    {
        $request->validate([
            'name'=>'required|string',
            'national_id'=>'required|unique:users|numeric',
            'email'=>'nullable|email|unique:users',
            'phone'=>'nullable|numeric|unique:users',
            'department_id'=>'exists:departments,id'
        ]);
        $doctor=[
            'full_name'=>$request->name,
            'national_id'=>$request->national_id,
            'role_id'=>2,
            'email' => $request->filled('email') ? $request->email : null,
            'phone'=>$request->phone??null,
            'password'=>Hash::make('123456')
        ];
        $user= User::create($doctor);
        StaffProfile::create([
            'user_id'=>$user->id,
            'department_id'=>$request->department_id
        ]);
        return response()->json([
            'message'=>'doctor added successfully',
            'doctor'=>$user,],200);
    }

    public function updateDoctor(Request $request,$id)
    {
        $doctor=User::findOrFail($id);
        $request->validate([
            'name'=>'required|string',
            'national_id'=>'required|numeric|unique:users,national_id,'.$doctor->id,
            'email'=>'nullable|email|unique:users,email,'.$doctor->id,
            'phone'=>'nullable|numeric|unique:users,phone,'.$doctor->id ,
            'department_id'=>'exists:departments,id'
        ]);
            $doctor->full_name=$request->name;
            $doctor->national_id=$request->national_id;
            $doctor->role_id=2;
            $doctor->email = $request->filled('email') ? $request->email : null;
            $doctor->phone=$request->filled('phone') ?$request->phone:null;
            $doctor->save();
            $staff = StaffProfile::updateOrCreate(
            ['user_id' => $doctor->id],
            ['department_id' => $request->department_id]
        );

            $user=User::with('staffprofile')
            ->where('id',$id)->get();
             return response()->json([
            'message'=>'doctor updated successfully',
            'doctor'=>$user,],200);


    }
}
