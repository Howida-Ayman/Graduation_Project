<?php

use App\Http\Controllers\Api\AuthController;
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

});





