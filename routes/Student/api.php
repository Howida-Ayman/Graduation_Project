<?php


use App\Http\Controllers\Api\User\LookupController;
use App\Http\Controllers\Api\User\UserController;
use App\Http\Controllers\Api\Library\LibraryController;
use App\Http\Controllers\Api\PreviousProject\PreviousProjectController;
use App\Http\Controllers\Api\Proposal\ProposalController;
use App\Http\Controllers\Api\Proposal\ProposalFormController;
use App\Http\Controllers\Api\SuggestedProject\SuggestedProjectController;
use App\Http\Controllers\Api\Team\TeamController;
use App\Http\Controllers\Api\Submission\SubmissionController;
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

Route::get('/my-team', [TeamController::class, 'index']);
Route::post('/my-team/leave', [TeamController::class, 'leave']);

// Proposals
Route::get('/proposal/form-data', [ProposalFormController::class, 'getFormData']);
Route::post('/proposal/submit', [ProposalController::class, 'store']);

    Route::get('/team/requirements', [SubmissionController::class, 'getTeamRequirements']);
    // رفع submission جديد
    Route::post('/submission/upload', [SubmissionController::class, 'upload']);



});



