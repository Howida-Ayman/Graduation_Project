<?php


use App\Http\Controllers\Api\Requests\Supervisor\SupervisionRequestsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum','supervisor'])->group(function()
{
    Route::prefix('supervisor')->group(function(){
    // Requests
    Route::get('requests',[SupervisionRequestsController::class,'getRequests']);
    Route::put('requests/{id}/respond',[SupervisionRequestsController::class,'respondToRequest']);
    });
});