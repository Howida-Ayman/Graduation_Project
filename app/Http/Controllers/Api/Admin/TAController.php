<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Imports\TAImport;
use App\Models\User;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

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
}
