<?php


use App\Http\Controllers\Api\Library\LibraryController;
use App\Http\Controllers\Api\PreviousProject\PreviousProjectController;
use App\Http\Controllers\Api\SuggestedProject\SuggestedProjectController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


//projects
Route::get('/library', [LibraryController::class, 'index']);


Route::prefix('library')->group(function () {
    Route::get('/', [LibraryController::class, 'index']);
    Route::get('/suggested', [LibraryController::class, 'suggested']);
    Route::get('/previous', [LibraryController::class, 'previous']);

    Route::get('/previous/{id}', [PreviousProjectController::class, 'show']);
    Route::get('/suggested/{id}', [SuggestedProjectController::class, 'show']);
    
});

Route::get('suggested-projects', [SuggestedProjectController::class, 'index']);
Route::get('previous-projects', [PreviousProjectController::class, 'index']);