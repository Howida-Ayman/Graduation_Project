<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\TimeLine\TimelineController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

//login
Route::post('login',[AuthController::class,'login'])->name('login');

//logout
Route::middleware('auth:sanctum')->group(function(){
Route::post('logout',[AuthController::class,'logout'])->name('logout');
});
