<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exports\StudentsExport;
use App\Http\Controllers\Controller;
use App\Imports\StudentsImport;
use App\Models\AcademicYear;
use App\Models\StudentEnrollment;
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
    $search = $request->search;
    $perPage = $request->per_page ?? 10;

    $activeAcademicYear = AcademicYear::where('is_active', true)->first();

    if (!$activeAcademicYear) {
        return response()->json([
            'message' => 'No active academic year found',
            'data' => []
        ], 200);
    }

    $students = User::with([
            'studentprofile.department',
            'enrollments' => function ($q) use ($activeAcademicYear) {
                $q->where('academic_year_id', $activeAcademicYear->id)
                  ->where('status', 'active')
                  ->with('academicYear');
            }
        ])
        ->where('role_id', 4)
        ->whereHas('enrollments', function ($q) use ($activeAcademicYear) {
            $q->where('academic_year_id', $activeAcademicYear->id)
              ->where('status', 'active');
        })
        ->when($search, function ($query) use ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%$search%")
                    ->orWhere('national_id', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%")
                    ->orWhereHas('studentprofile.department', function ($q) use ($search) {
                        $q->where('name', 'like', "%$search%");
                    });
            });
        })
        ->paginate($perPage);

    $students->getCollection()->transform(function ($student) {
        $enrollment = $student->enrollments->first();

        return [
            'id' => $student->id,
            'full_name' => $student->full_name,
            'national_id' => $student->national_id,
            'email' => $student->email,
            'phone' => $student->phone,
            'is_active' => $student->is_active,
            'department' => $student->studentprofile?->department?->name,
            'gpa' => $student->studentprofile?->gpa,
            'academic_year' => $enrollment?->academicYear?->code,
            'enrollment_status' => $enrollment?->status,
        ];
    });

    return response()->json([
        'message' => 'Students retrieved successfully',
        'active_academic_year' => [
            'id' => $activeAcademicYear->id,
            'code' => $activeAcademicYear->code,
        ],
        'data' => $students
    ], 200);
}

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,xlsx'
        ]);

        Excel::import(new StudentsImport, $request->file('file'));

        return response()->json([
            'message' => 'Students imported successfully'
        ], 201);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'national_id' => 'required|unique:users|digits:14',
            'email' => 'nullable|email|unique:users',
            'phone' => 'nullable|digits:11|unique:users',
            'department_id' => 'nullable|exists:departments,id',
            'gpa' => 'nullable|decimal:1,2'
        ]);

        $activeAcademicYear = AcademicYear::where('is_active', true)->first();

        if (!$activeAcademicYear) {
            return response()->json([
                'message' => 'No active academic year found'
            ], 400);
        }

        $user = DB::transaction(function () use ($request, $activeAcademicYear) {
            $student = [
                'full_name' => $request->name,
                'national_id' => $request->national_id,
                'role_id' => 4,
                'email' => $request->filled('email') ? $request->email : null,
                'phone' => $request->filled('phone') ? $request->phone : null,
                'password' => Hash::make('123456'),
                'is_active' => true,
            ];

            $user = User::create($student);

            if ($request->filled('department_id')) {
                StudentProfile::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'department_id' => $request->department_id,
                        'gpa' => $request->filled('gpa') ? $request->gpa : null
                    ]
                );

                StudentEnrollment::updateOrCreate(
                    [
                        'student_user_id' => $user->id,
                        'academic_year_id' => $activeAcademicYear->id,
                    ],
                    [
                        'status' => 'active'
                    ]
                );
            }

            return $user->load(['studentprofile.department', 'enrollments']);
        });

        return response()->json([
            'message' => 'Student added successfully',
            'data' => $user,
        ], 201);
    }

    public function export()
    {
        $fileName = 'students_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        Excel::store(new StudentsExport, 'Students/' . $fileName, 'public');
        $url = asset('storage/Students/' . $fileName);

        return response()->json([
            'message' => 'Students exported successfully',
            'file_name' => $fileName,
            'download_url' => $url
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $student = User::where('role_id', 4)->findOrFail($id);

        $request->validate([
            'name' => 'nullable|string',
            'national_id' => 'nullable|digits:14|unique:users,national_id,' . $student->id,
            'email' => 'nullable|email|unique:users,email,' . $student->id,
            'phone' => 'nullable|digits:11|unique:users,phone,' . $student->id,
            'department_id' => 'nullable|exists:departments,id',
            'gpa' => 'nullable|decimal:1,2',
            'is_active' => 'nullable|boolean'
        ]);

        DB::transaction(function () use ($request, $student) {
            $student->update([
                'full_name' => $request->filled('name') ? $request->name : $student->full_name,
                'national_id' => $request->filled('national_id') ? $request->national_id : $student->national_id,
                'email' => $request->filled('email') ? $request->email : $student->email,
                'phone' => $request->filled('phone') ? $request->phone : $student->phone,
                'is_active' => $request->filled('is_active') ? $request->is_active : $student->is_active,
            ]);

            if ($request->filled('department_id') || $request->has('gpa')) {
                StudentProfile::updateOrCreate(
                    ['user_id' => $student->id],
                    [
                        'department_id' => $request->filled('department_id')
                            ? $request->department_id
                            : optional($student->studentprofile)->department_id,
                        'gpa' => $request->has('gpa')
                            ? $request->gpa
                            : optional($student->studentprofile)->gpa,
                    ]
                );
            }
        });

        $student = User::with('studentprofile.department')->find($id);

        return response()->json([
            'message' => 'Student updated successfully',
            'data' => $student
        ], 200);
    }

    public function deactivateAllStudents()
    {
        User::where('role_id', 4)->update([
            'is_active' => false
        ]);

        return response()->json([
            'message' => 'All students deactivated successfully'
        ], 200);
    }


}