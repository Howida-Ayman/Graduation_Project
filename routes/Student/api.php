<?php


use App\Http\Controllers\Api\User\LookupController;
use App\Http\Controllers\Api\User\UserController;
use App\Http\Controllers\Api\User\RequestController;
use App\Http\Controllers\Api\Library\LibraryController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PreviousProject\PreviousProjectController;
use App\Http\Controllers\Api\Proposal\ProposalController;
use App\Http\Controllers\Api\Proposal\ProposalFormController;
use App\Http\Controllers\Api\SuggestedProject\SuggestedProjectController;
use App\Http\Controllers\Api\Team\TeamController;
use App\Http\Controllers\Api\Submission\SubmissionController;
use App\Http\Controllers\Api\Supervisor\SupervisionController;
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

//milestone active
Route::get('/milestone/active', [SubmissionController::class, 'getActiveMilestones']);
Route::post('/submission/upload', [SubmissionController::class, 'uploadSubmission']);
 Route::get('/team/milestones/status', [SubmissionController::class, 'getTeamMilestonesWithStatus']);


Route::get('/notifications', [NotificationController::class, 'index']);
Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);


Route::get('/available/teams', [RequestController::class, 'availableTeams']);
Route::get('/available/students', [RequestController::class, 'availableStudents']);

Route::post('/requests', [RequestController::class, 'sendRequest']);
Route::get('/requests/received', [RequestController::class, 'receivedRequests']);
Route::get('/requests/sent', [RequestController::class, 'sentRequests']);
Route::post('/requests/{id}/respond', [RequestController::class, 'respondRequest']);


    // المشرفين
    Route::get('/available/supervisors', [SupervisionController::class, 'availableSupervisors']);
    Route::post('/supervision-requests', [SupervisionController::class, 'requestSupervision']);
    Route::get('/supervision-requests/received', [SupervisionController::class, 'receivedRequests']);
    Route::post('/supervision-requests/{id}/respond', [SupervisionController::class, 'respondRequest']);
    Route::get('/my-team/supervisors', [SupervisionController::class, 'getTeamSupervisors']);

});


