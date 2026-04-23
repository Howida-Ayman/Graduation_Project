<?php


use App\Http\Controllers\Api\User\LookupController;
use App\Http\Controllers\Api\User\UserController;
use App\Http\Controllers\Api\Library\LibraryController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PreviousProject\PreviousProjectController;
use App\Http\Controllers\Api\Proposal\ProposalController;
use App\Http\Controllers\Api\Proposal\ProposalFormController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SuggestedProject\SuggestedProjectController;
use App\Http\Controllers\Api\Team\TeamController;
use App\Http\Controllers\Api\Submission\SubmissionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Requests\Students\StudentsRequestsController;
use App\Http\Controllers\Api\Requests\Supervisor\SupervisionRequestsController;
use App\Http\Controllers\Api\TermsController;
use App\Http\Controllers\Api\TimeLine\TimelineController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::middleware('auth:sanctum')->group(function(){
//user
Route::get('profile',[UserController::class,'profile'])->name('profile');
Route::post('profile',[UserController::class,'update'])->name('profile.update');

// تغيير كلمة المرور (فانكشن منفصلة)
Route::put('/profile/change-password', [UserController::class, 'changePassword']);

Route::get('academic-years', [LookupController::class, 'academicYears']);
Route::get('departments', [LookupController::class, 'departments']);

//favorites
Route::post('/library/suggested/{id}/favorite', [LibraryController::class, 'toggleSuggestedFavorite']);
Route::post('/library/previous/{id}/favorite', [LibraryController::class, 'togglePreviousFavorite']);
Route::get('/library/favorites', [LibraryController::class, 'favorites']);


//teams
Route::get('/my-team', [TeamController::class, 'index']);
Route::post('/my-team/leave', [TeamController::class, 'leave']);
Route::post('/my-team/note', [TeamController::class, 'leaveNote']);


// Proposals
Route::get('/proposal/form-data', [ProposalFormController::class, 'getFormData']);
Route::post('/proposal/submit', [ProposalController::class, 'store']);

//milestone active
Route::get('/milestone/active', [SubmissionController::class, 'getActiveMilestones']);
Route::post('/submission/upload', [SubmissionController::class, 'uploadSubmission']);
// Route::get('/team/milestones/status', [SubmissionController::class, 'getTeamMilestonesWithStatus']);


Route::get('/notifications', [NotificationController::class, 'index']);
Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);

Route::get('/available/teams', [StudentsRequestsController::class, 'availableTeams']);
Route::get('/available/students', [StudentsRequestsController::class, 'availableStudents']);

Route::post('/requests', [StudentsRequestsController::class, 'sendRequest']);
Route::get('/requests/received', [StudentsRequestsController::class, 'receivedRequests']);
Route::get('/requests/sent', [StudentsRequestsController::class, 'sentRequests']);
Route::post('/requests/{id}/respond', [StudentsRequestsController::class, 'respondRequest']);


    // المشرفين
    Route::get('/available/supervisors', [SupervisionRequestsController::class, 'availableSupervisors']);
    Route::post('/supervision-requests', [SupervisionRequestsController::class, 'requestSupervision']);
  
// Route::get('/my-timeline', [TimelineController::class, 'index']);
Route::get('/my-timeline/{id}', [TimelineController::class, 'show']);

// Route::get('/timeline', [TimelineController::class, 'index']);
// Route::get('/timeline/{id}', [TimelineController::class, 'show']);



    // Reports
    Route::prefix('reports')->group(function () {
    Route::post('/', [ReportController::class, 'store']);           // Submit report
    Route::get('/my-reports', [ReportController::class, 'myReports']); // Get user's reports
    });
    
 
});





