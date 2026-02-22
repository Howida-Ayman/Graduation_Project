<?php

use App\Http\Controllers\Api\Admin\DoctorController;
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


});
});