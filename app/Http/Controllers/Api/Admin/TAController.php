<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exports\TAExport;
use App\Http\Controllers\Controller;
use App\Imports\TAImport;
use App\Models\StaffProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;

use function Symfony\Component\Clock\now;

class TAController extends Controller
{
    public function index(Request $request)
    {
        $perpage= $request->per_page??10;
        $TA=User::with('staffprofile')->where('role_id',3)->paginate($perpage);
        return response()->json([
            'message'=>'Teacher Assistant retrieved successfully',
            'data'=>$TA
        ],200);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file'=>'required|mimes:csv,xlsx'
        ]);
        Excel::import(new TAImport,$request->file('file'));
        return response()->json([
            'message'=> 'TA imported successfully'],200);
    }

    public function export(Request $request)
    {
        $fileName='TA_'.now()->format('Y-m-d_H-i-s').'.xlsx';
        Excel::store(new TAExport,'TA/'.$fileName,'public');
        $url=asset('storage/TA/'.$fileName);
        return response()->json([
            'message'=>'TA exported successfully',
            'file_name'=>$fileName,
            'download_url'=>$url
        ],200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'=>'required|string',
            'national_id'=>'required|unique:users|numeric',
            'email'=>'nullable|email|unique:users',
            'phone'=>'nullable|numeric|unique:users',
            'department_id'=>'nullable|exists:departments,id'
        ]);
        $doctor=[
            'full_name'=>$request->name,
            'national_id'=>$request->national_id,
            'role_id'=>3,
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
        return response()->json([
            'message'=>'TA added successfully',
            'doctor'=>$user,],200);
    }
    public function update(Request $request,$id)
    {
        $TA=User::findOrFail($id);
        $request->validate(
            [
                'name'=>"nullable|string",
                'national_id'=>'nullable|numeric|unique:users,national_id,'.$TA->id,
                'email'=>'nullable|email|unique:users,email,'.$TA->id,
                'phone'=>'nullable|numeric|unique:users,phone,'.$TA->id,
                'department_id'=>'nullable|exists:departments,id',
            ]
        );
        $TA->full_name   = $request->filled('name') ? $request->name : $TA->full_name;
        $TA->national_id = $request->filled('national_id') ? $request->national_id : $TA->national_id;
        $TA->email       = $request->filled('email') ? $request->email : $TA->email;
        $TA->phone       = $request->filled('phone') ? $request->phone : $TA->phone;
        $TA->save();
        if($request->filled('department_id')){
            StaffProfile::updateOrCreate(
                ['user_id'=>$TA->id],
                ['department_id'=>$request->department_id]
            );
        }
        $user=User::with('staffprofile')->find($id);
        return response()->json([
            'message'=>'TA updated successfully',
            'data'=>$user
        ],200);

    }
}
