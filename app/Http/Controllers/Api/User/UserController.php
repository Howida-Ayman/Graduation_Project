<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\User\UpdateProfileRequest;
use App\Http\Resources\ProfileResource;
use App\Models\User;
// use Illuminate\Container\Attributes\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB ;

class UserController extends Controller
{
   public function profile(Request $request)
{
    $user = $request->user()->load([
        'role',
        'studentProfile.department',
        'staffprofile.department',
    ]);

    return response()->json([
        'status' => true,
        'data' => new ProfileResource($user)
    ], 200);
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

            // لو Student
            if ($user->role?->code === 'student') {
                $user->studentProfile()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'department_id' => $request->department_id,
                        'gpa' => $request->gpa,
                    ]
                );
            }

            // لو Doctor أو TA
            if (in_array($user->role?->code, ['doctor', 'TA', 'ta'])) {
                $user->staffprofile()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'department_id' => $request->department_id,
                    ]
                );
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Profile updated successfully'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong'
            ], 500);
        }
    }
public function toggleUserStatus($id)
{
    $user = User::findOrFail($id);

    // قلب الحالة
    $user->is_active = !$user->is_active;
    $user->save();

    return response()->json([
        'message' => $user->is_active
            ? 'User activated successfully'
            : 'User deactivated successfully',
        'is_active' => $user->is_active
    ], 200);
}

}
