<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\User\UpdateProfileRequest;
use App\Http\Resources\ProfileResource;
// use Illuminate\Container\Attributes\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB ;

class UserController extends Controller
{
    public function profile(Request $request)
{
    $user = $request->user()->load(
        'studentProfile.department',
    );

    return response()->json([
        'status' => true,
        'data' => new ProfileResource($user)
    ]);
}

public function update(UpdateProfileRequest $request)
{
    $user = $request->user();

    DB::beginTransaction();

    try {

        // تحديث جدول users
        $user->update([
            'full_name' => $request->full_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'track_name' => $request->track_name,
        ]);

    
        // تحديث أو إنشاء student_profile
        $user->studentProfile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'department_id' => $request->department_id,
                'gpa' => $request->gpa,
            ]
        );

        DB::commit();

        return response()->json([
            'status' => true,
            'message' => 'Profile updated successfully'
        ]);

    } catch (\Exception $e) {

        DB::rollBack();

        return response()->json([
            'status' => false,
            'message' => 'Something went wrong'
        ], 500);
    }
}






























}
