<?php


use App\Http\Controllers\Api\Requests\Supervisor\SupervisionRequestsController;
use App\Http\Controllers\Api\Submission\SubmissionController;
use App\Http\Controllers\Api\Supervisor\AnnouncementController;
use App\Http\Controllers\Api\Supervisor\TeamManagementController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function()
{
    //routes for both Doctor & TA
    Route::middleware('supervisor')->group(function()
    {
        Route::prefix('supervisor')->group(function()
        {
           // Requests
           Route::get('requests',[SupervisionRequestsController::class,'getRequests']);
           Route::put('requests/{id}/respond',[SupervisionRequestsController::class,'respondToRequest']);
           // team management
           Route::get('/team-management', [TeamManagementController::class, 'index']);
           Route::get('/teams/{teamId}', [TeamManagementController::class, 'viewTeam']);
           // announcement
           Route::post('/announcements', [AnnouncementController::class, 'store']);
           Route::get('/my-announcements', [AnnouncementController::class, 'index']);
           Route::get('/teams_list', [AnnouncementController::class, 'TeamsList']);
        });
    });

    //routes for only Doctor 
    Route::middleware('doctor')->group(function()
    {
        Route::prefix('doctor')->group(function()
        {
            Route::post('/submission-files/{file}/feedback', [SubmissionController::class, 'addFeedback']);
            Route::post('/teams/{team}/milestones/{milestone}/grade', [SubmissionController::class, 'addGrade']);
            Route::delete('/teams/delete_grade', [SubmissionController::class, 'deleteGrade']);
        });
    
    });



});