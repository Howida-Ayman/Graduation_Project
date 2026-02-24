<?php


use App\Http\Controllers\Api\User\LookupController;
use App\Http\Controllers\Api\User\UserController;
use App\Http\Controllers\Api\Library\LibraryController;
use App\Http\Controllers\Api\PreviousProject\PreviousProjectController;
use App\Http\Controllers\Api\SuggestedProject\SuggestedProjectController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::middleware('auth:sanctum')->group(function(){
//user
Route::get('profile',[UserController::class,'profile'])->name('profile');
Route::put('profile',[UserController::class,'update'])->name('profile.update');
Route::get('academic-years', [LookupController::class, 'academicYears']);
Route::get('departments', [LookupController::class, 'departments']);
Route::get('/library/favorites', [LibraryController::class, 'favorites']);





});



