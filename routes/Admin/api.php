<?php

use App\Http\Controllers\Api\Admin\AdminController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware(['auth:sanctum','admin'])->group(function(){
//import & export users
Route::middleware('admin')->prefix('admin/doctor')->group(function(){
    Route::get('/',[AdminController::class,'all'])->name('doctor.all');
    Route::post('/import',[AdminController::class,'ImportDoctors'])->name('doctor.import');
    Route::get('/export',[AdminController::class,'ExportDoctors'])->name('doctor.export');
    Route::post('/store',[AdminController::class,'storeDoctor']);
    Route::put('/{id}/update',[AdminController::class,'updateDoctor']); 
});
});