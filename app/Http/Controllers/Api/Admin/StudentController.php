<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Imports\StudentsImport;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $perpage=$request->per_page??10;
        $students=User::with('studentprofile')
        ->where('role_id',4)
        ->paginate($perpage);
        return response()->json([
            'message'=>'Students Retrived Successfully',
            'data'=>$students
        ],200);
    }
    public function import(Request $request)
    {
        $request->validate([
            'file'=>'required|mimes:csv,xlsx'
        ]);
        Excel::import(new StudentsImport,$request->file('file'));
        return response()->json([
            'message'=>'students imported successfully'
        ],201);
    }
    public function store(Request $request)
    {
        $request->validate([
            'name'=>'required|string',
            'national_id'=>'required|unique:users|numeric',
            'email'=>'nullable|email|unique:users',
            'phone'=>'nullable|numeric|unique:users',
            'department_id'=>'nullable|exists:departments,id',
            'gpa'=>'nullable|decimal:1,2'
        ]);
        $user=DB::transaction(function() use($request)
        {

        
        $student=[
            'full_name'=>$request->name,
            'national_id'=>$request->national_id,
            'role_id'=>4,
            'email' => $request->filled('email') ? $request->email : null,
            'phone'=>$request->filled('phone')?$request->phone:null,
            'password'=>Hash::make('123456')
        ];
        $user=User::create($student);
       if($request->filled('department_id'))
        {
            StudentProfile::updateOrCreate(
                ['user_id'=>$user->id],
             ['department_id'=>$request->department_id,
             'gpa'=>$request->filled('gpa')?$request->gpa:null ]
             );
        }
         return $user->load('studentprofile');
        });
        return response()->json([
            'message'=>'student added successfully',
            'data'=>$user,
        ],201);
    }
}