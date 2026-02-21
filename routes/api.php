<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\User\LookupController;
use App\Http\Controllers\Api\User\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
//login
Route::post('login',[AuthController::class,'login'])->name('login');
Route::middleware('auth:sanctum')->group(function(){
//user
Route::get('profile',[UserController::class,'profile'])->name('profile');
Route::put('profile',[UserController::class,'update'])->name('profile.update');
Route::get('academic-years', [LookupController::class, 'academicYears']);
Route::get('departments', [LookupController::class, 'departments']);

//logout
Route::post('logout',[AuthController::class,'logout'])->name('logout');
//import & export users
Route::middleware('admin')->group(function(){
Route::get('doctors',[UserController::class,'all'])->name('doctors.all');
Route::post('doctors/import',[UserController::class,'ImportDoctors'])->name('doctors.import');
Route::get('doctors/export',[UserController::class,'ExportDoctors'])->name('doctors.export');
});



});