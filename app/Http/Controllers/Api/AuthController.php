<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        if(Auth::attempt(['national_id'=>$request->national_id,'password'=>$request->password]))
            {
                $user=Auth::user();
                $token = $user->createToken('api_login')->plainTextToken;
                $data=[
                    'id'=>$user->id,
                    'role_id'=>$user->role_id,
                    'national_id'=>$user->national_id,
                    'full_name'=>$user->full_name,
                    'email'=>$user->email,
                    'track_name'=>$user->track_name,
                    'profile_image_url'=>$user->profile_image_url,
                    'phone'=>$user->phone
                ];
                return response()->json([
                    'message'=>'Login Successful',
                    'user'=>$data,
                    'token'=>$token],200);
            }
        else{
            return response()->json([
            'message'=>'Invalid credentials',
            ],401);
            }
    }

    public function logout(Request $request)
    {
       $request->user()->CurrentAccessToken()->delete();
       return response()->json([
            'message'=>'Logged out successfully ',
            ],200);
    }
}
