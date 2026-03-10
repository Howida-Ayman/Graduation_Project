<?php

use App\Http\Controllers\Api\Admin\AcademicYearsController;
use App\Http\Controllers\Api\Admin\DepartmentController;
use App\Http\Controllers\Api\Library\LibraryController;
use App\Http\Controllers\Api\PreviousProject\PreviousProjectController;
use App\Http\Controllers\Api\SuggestedProject\SuggestedProjectController;
use App\Http\Controllers\Api\User\LookupController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


//projects  --> library ,suggested , previous and details of them

Route::prefix('library')->group(function () {
    Route::get('/', [LibraryController::class, 'index']);
    Route::get('/suggested', [LibraryController::class, 'suggested']);
    Route::get('/previous', [LibraryController::class, 'previous']);

    Route::get('/previous/{id}', [PreviousProjectController::class, 'show']);
    Route::get('/suggested/{id}', [SuggestedProjectController::class, 'show']);
    
});

//project types
Route::get('project-types',[LookupController::class,'getprojectTypes']);

//get deprtments
Route::get('departments',[DepartmentController::class,'index'])->name('departments.index');
//get academic years
 Route::get('academic-years',[AcademicYearsController::class,'index'])->name('academic-years.index');