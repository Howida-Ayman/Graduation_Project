<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Imports\DoctorsImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class UserController extends Controller
{
    public function ImportDoctors(Request $request)
    {
        $request->validate(
           [ 'file'=>"required|mimes:xlsx,csv"]
        );
        Excel::import(new DoctorsImport,$request->file('file'));
        return response()->json([
            'status'=>true,
            'message'=>'imported successfully',
        ],200);

    }
}
