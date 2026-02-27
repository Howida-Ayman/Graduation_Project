<?php

use App\Http\Controllers\Api\Admin\AcademicYearsController;
use App\Http\Controllers\Api\Admin\DepartmentController;
use App\Http\Controllers\Api\Admin\DoctorController;
use App\Http\Controllers\Api\Admin\ProjectRuleController;
use App\Http\Controllers\Api\Admin\StudentController;
use App\Http\Controllers\Api\Admin\TAController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware(['auth:sanctum','admin'])->group(function(){

Route::prefix('admin')->group(function(){
    //import & export doctors
    Route::prefix('doctor')->group(function(){
    Route::get('/',[DoctorController::class,'index'])->name('doctor.index');
    Route::post('/import',[DoctorController::class,'import'])->name('doctor.import');
    Route::get('/export',[DoctorController::class,'export'])->name('doctor.export');
    Route::post('/store',[DoctorController::class,'store'])->name(name: 'doctor.store'); 
    });

    //import & export TA
    Route::prefix('TA')->group(function(){
    Route::get('/',[TAController::class,'index'])->name('TA.index');
    Route::post('/import',[TAController::class,'import'])->name('TA.import');
    Route::get('/export',[TAController::class,'export'])->name('TA.export');
    Route::post('/store',[TAController::class,'store'])->name(name: 'TA.store');
    Route::put('/{id}/update',[TAController::class,'update'])->name('TA.update'); 
    });

    //import & export students
    Route::prefix('student')->group(function(){
    Route::get('/',[StudentController::class,'index'])->name('student.index');
    Route::post('/import',[StudentController::class,'import'])->name('student.import');
    Route::get('/export',[StudentController::class,'export'])->name('student.export');
    Route::post('/store',[StudentController::class,'store'])->name(name: 'student.store');
    Route::put('/{id}/update',[StudentController::class,'update'])->name('student.update'); 
    });

    //Departments
    Route::prefix('departments')->group(function(){
        Route::post('/',[DepartmentController::class,'store'])->name('departments.store');
        Route::put('/{id}/update',[DepartmentController::class,'update'])->name('departments.update');
    });

    //ِAcademic Years
        Route::prefix('academic-years')->group(function(){
        Route::post('/',[AcademicYearsController::class,'store'])->name('academic-years.store');
        Route::put('/{id}/update',[AcademicYearsController::class,'update'])->name('academic-years.update');
    });

    //team & project rules
    Route::prefix('project_rules')->group(function(){
        Route::get('/',[ProjectRuleController::class,'index']);
        Route::put('store/team rules',[ProjectRuleController::class,'UpdateTeamRules']);
        Route::post('/{section}',[ProjectRuleController::class,'StoreRules']);
        Route::delete('/{id}/delete',[ProjectRuleController::class,'deleteRule']);
    });
});
});