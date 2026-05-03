<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exports\StudentsExport;
use App\Http\Controllers\Controller;
use App\Imports\StudentsImport;
use App\Models\AcademicYear;
use App\Models\ProjectCourse;
use App\Models\StudentEnrollment;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class StudentController extends Controller
{
public function index(Request $request)
{
    $search = $request->search;
    $perPage = $request->per_page ?? 10;
    $courseOrder = $request->course ?? 1; // 1 = capstone I, 2 = capstone II

    $activeAcademicYear = AcademicYear::where('is_active', true)->first();

    if (!$activeAcademicYear) {
        return response()->json([
            'message' => 'No active academic year found.',
            'data' => []
        ], 200);
    }

    $projectCourse = ProjectCourse::where('order', $courseOrder)->first();

    if (!$projectCourse) {
        return response()->json([
            'message' => 'Invalid project course selected.',
            'data' => []
        ], 422);
    }

    $students = User::with([
            'studentprofile.department',
            'enrollments' => function ($q) use ($activeAcademicYear, $projectCourse) {
                $q->where('academic_year_id', $activeAcademicYear->id)
                  ->where('project_course_id', $projectCourse->id)
                  ->where('status', 'in_progress')
                  ->with(['academicYear', 'projectCourse']);
            }
        ])
        ->where('role_id', 4)
        ->whereHas('enrollments', function ($q) use ($activeAcademicYear, $projectCourse) {
            $q->where('academic_year_id', $activeAcademicYear->id)
              ->where('project_course_id', $projectCourse->id)
              ->where('status', 'in_progress');
        })
        ->when($search, function ($query) use ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('national_id', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('studentprofile.department', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
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
            'project_course' => $enrollment?->projectCourse?->name,
            'enrollment_status' => $enrollment?->status,
        ];
    });

    return response()->json([
        'message' => 'Students retrieved successfully.',
        'active_academic_year' => [
            'id' => $activeAcademicYear->id,
            'code' => $activeAcademicYear->code,
        ],
        'project_course' => [
            'id' => $projectCourse->id,
            'name' => $projectCourse->name,
            'order' => $projectCourse->order,
        ],
        'data' => $students
    ], 200);
}

public function import(Request $request)
{
    try {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
            'course' => 'required|in:1,2',
        ], [
            'file.required' => 'Please upload an Excel file.',
            'file.mimes' => 'The file must be a valid Excel format (xlsx, xls, csv).',
            'course.required' => 'Please select a project course.',
            'course.in' => 'Invalid course selected.',
        ]);

        $projectCourse = ProjectCourse::where('order', $request->course)->first();

        if (!$projectCourse) {
            return response()->json([
                'message' => 'The selected project course was not found.',
            ], 404);
        }

        //  Import
        $import = new StudentsImport($request->course);

        Excel::import($import, $request->file('file'));

        return response()->json([
            'message' => 'Students imported successfully.',
            'course' => $projectCourse->name,
            'processed_rows' => $import->getProcessedRows(),
        ], 200);

    } catch (ValidationException $e) {

        return response()->json([
            'message' => 'Validation error occurred.',
            'errors' => $e->errors(),
        ], 422);

    } catch (\Exception $e) {

        return response()->json([
            'message' => 'An unexpected error occurred during import.',
        ], 500);
    }
}
public function show($id)
{
    $student = User::where('role_id', 4)
        ->with([
            'studentprofile.department',
            'enrollments.projectCourse',
            'enrollments.academicYear'
        ])
        ->findOrFail($id);

    $activeAcademicYear = AcademicYear::where('is_active', true)->first();

    $project1Enrollment = $student->enrollments
        ->where('academic_year_id', $activeAcademicYear?->id)
        ->first(fn ($e) => $e->projectCourse?->order == 1);

    $project2Enrollment = $student->enrollments
        ->where('academic_year_id', $activeAcademicYear?->id)
        ->first(fn ($e) => $e->projectCourse?->order == 2);

    return response()->json([
        'message' => 'Student retrieved successfully.',
        'data' => [
            'id' => $student->id,
            'full_name' => $student->full_name,
            'national_id' => $student->national_id,
            'email' => $student->email,
            'phone' => $student->phone,
            'is_active' => $student->is_active,
            'department_id' => $student->studentprofile?->department_id,
            'department' => $student->studentprofile?->department?->name,
            'gpa' => $student->studentprofile?->gpa,

            'project1_status' => $project1Enrollment?->status,
            'project2_status' => $project2Enrollment?->status,

            'can_edit_project1_status' => (bool) $project1Enrollment,
            'can_edit_project2_status' => (bool) $project2Enrollment,
        ]
    ], 200);
}

public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'national_id' => 'required|digits:14|unique:users,national_id',
        'email' => 'nullable|email|unique:users,email',
        'phone' => 'nullable|digits:11|unique:users,phone',
        'department_id' => 'nullable|exists:departments,id',
        'gpa' => 'nullable|numeric|min:0|max:4',
        'course' => 'required|in:1,2',
    ], [
        'course.required' => 'Please select a project course.',
        'course.in' => 'Invalid project course selected.',
    ]);

    //  منع Project 2 للطالب الجديد
    if ((int) $request->course === 2) {
        return response()->json([
            'message' => 'A new student cannot be added directly to Capstone Project II. The student must pass Capstone Project I first.'
        ], 422);
    }

    $activeAcademicYear = AcademicYear::where('is_active', true)->first();

    if (!$activeAcademicYear) {
        return response()->json([
            'message' => 'No active academic year found.'
        ], 400);
    }

    $projectCourse = ProjectCourse::where('order', $request->course)->first();

    if (!$projectCourse) {
        return response()->json([
            'message' => 'The selected project course was not found.'
        ], 422);
    }

    $user = DB::transaction(function () use ($request, $activeAcademicYear, $projectCourse) {

        $user = User::create([
            'full_name' => $request->name,
            'national_id' => $request->national_id,
            'role_id' => 4,
            'email' => $request->filled('email') ? $request->email : null,
            'phone' => $request->filled('phone') ? $request->phone : null,
            'password' => Hash::make('123456'),
            'is_active' => true,
        ]);

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
                'project_course_id' => $projectCourse->id,
            ],
            [
                'status' => 'in_progress'
            ]
        );

        return $user->load(['studentprofile.department', 'enrollments.projectCourse']);
    });
    $project1 = $user->enrollments->first(fn ($e) => $e->projectCourse?->order == 1);
    $project2 = $user->enrollments->first(fn ($e) => $e->projectCourse?->order == 2);

    $responseData = [
        'id' => $user->id,
        'full_name' => $user->full_name,
        'national_id' => $user->national_id,
        'email' => $user->email,
        'phone' => $user->phone,
        'is_active' => $user->is_active,
        'department' => $user->studentprofile?->department?->name,
        'gpa' => $user->studentprofile?->gpa,
        'project1_status' => $project1?->status,
        'project2_status' => $project2?->status,
    ];

    return response()->json([
        'message' => 'Student created successfully.',
        'data' => $responseData
    ], 201);
}
public function update(Request $request, $id)
{
    $student = User::where('role_id', 4)->findOrFail($id);

    $request->validate([
        'name' => 'nullable|string|max:255',
        'national_id' => 'nullable|digits:14|unique:users,national_id,' . $student->id,
        'email' => 'nullable|email|unique:users,email,' . $student->id,
        'phone' => 'nullable|digits:11|unique:users,phone,' . $student->id,
        'department_id' => 'nullable|exists:departments,id',
        'gpa' => 'nullable|numeric|min:0|max:4',

        'project1_status' => 'nullable|in:in_progress,passed,failed',
        'project2_status' => 'nullable|in:in_progress,passed,failed',
    ]);

    $activeAcademicYear = AcademicYear::where('is_active', true)->first();

    if (!$activeAcademicYear) {
        return response()->json([
            'message' => 'No active academic year found.'
        ], 400);
    }

    $project1 = ProjectCourse::where('order', 1)->first();
    $project2 = ProjectCourse::where('order', 2)->first();

    DB::transaction(function () use ($request, $student, $activeAcademicYear, $project1, $project2) {

        $student->update([
            'full_name' => $request->filled('name') ? $request->name : $student->full_name,
            'national_id' => $request->filled('national_id') ? $request->national_id : $student->national_id,
            'email' => $request->filled('email') ? $request->email : $student->email,
            'phone' => $request->filled('phone') ? $request->phone : $student->phone,
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

        //  Project 1
        if ($request->filled('project1_status') && $project1) {
            StudentEnrollment::updateOrCreate(
                [
                    'student_user_id' => $student->id,
                    'academic_year_id' => $activeAcademicYear->id,
                    'project_course_id' => $project1->id,
                ],
                [
                    'status' => $request->project1_status
                ]
            );
        }

        //  Project 2
        if ($request->filled('project2_status') && $project2) {

            //  منع Project 2 بدون نجاح Project 1
            $project1Enrollment = StudentEnrollment::where('student_user_id', $student->id)
                ->where('academic_year_id', $activeAcademicYear->id)
                ->where('project_course_id', $project1->id)
                ->first();

            if (!$project1Enrollment || $project1Enrollment->status !== 'passed') {
                abort(422, 'Student must pass Capstone Project I before being assigned to Project II.');
            }

            //  لو فشل في Project 2 → يفشل في Project 1
            if ($request->project2_status === 'failed') {
                StudentEnrollment::updateOrCreate(
                    [
                        'student_user_id' => $student->id,
                        'academic_year_id' => $activeAcademicYear->id,
                        'project_course_id' => $project1->id,
                    ],
                    [
                        'status' => 'failed'
                    ]
                );
            }

            StudentEnrollment::updateOrCreate(
                [
                    'student_user_id' => $student->id,
                    'academic_year_id' => $activeAcademicYear->id,
                    'project_course_id' => $project2->id,
                ],
                [
                    'status' => $request->project2_status
                ]
            );
        }
    });

    $student = User::with(['studentprofile.department', 'enrollments.projectCourse'])->find($id);

    // 🔥 Response نظيف
    $project1 = $student->enrollments->first(fn ($e) => $e->projectCourse?->order == 1);
    $project2 = $student->enrollments->first(fn ($e) => $e->projectCourse?->order == 2);

    $responseData = [
        'id' => $student->id,
        'full_name' => $student->full_name,
        'national_id' => $student->national_id,
        'email' => $student->email,
        'phone' => $student->phone,
        'is_active' => $student->is_active,
        'department' => $student->studentprofile?->department?->name,
        'gpa' => $student->studentprofile?->gpa,
        'project1_status' => $project1?->status,
        'project2_status' => $project2?->status,
    ];

    return response()->json([
        'message' => 'Student updated successfully.',
        'data' => $responseData
    ], 200);
}

public function export(Request $request)
{
    $request->validate([
        'course' => 'required|in:1,2',
    ], [
        'course.required' => 'Please select a project course.',
        'course.in' => 'Invalid project course selected.',
    ]);

    $projectCourse = ProjectCourse::where('order', $request->course)->first();

    if (!$projectCourse) {
        return response()->json([
            'message' => 'The selected project course was not found.'
        ], 422);
    }

    $courseName = $request->course == 1 ? 'capstone_project_1' : 'capstone_project_2';

    $fileName = $courseName . '_students_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

    Excel::store(
        new StudentsExport($request->course),
        'Students/' . $fileName,
        'public'
    );

    $url = asset('storage/Students/' . $fileName);

    return response()->json([
        'message' => 'Students exported successfully.',
        'project_course' => $projectCourse->name,
        'file_name' => $fileName,
        'download_url' => $url,
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