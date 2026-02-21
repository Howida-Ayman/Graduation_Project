<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
//login
Route::post('login',[AuthController::class,'login'])->name('login');
Route::middleware('auth:sanctum')->group(function(){
//logout
Route::post('logout',[AuthController::class,'logout'])->name('logout');
//import & export users
Route::middleware('admin')->group(function(){
Route::prefix('doctor')->group(function()
{
    Route::get('/',[UserController::class,'all'])->name('doctor.all');
    Route::post('/import',[UserController::class,'ImportDoctors'])->name('doctor.import');
    Route::get('/export',[UserController::class,'ExportDoctors'])->name('doctor.export');
    Route::post('/store',[UserController::class,'storeDoctor']);
    Route::put('/{id}/update',[UserController::class,'updateDoctor']);
});  
});



});