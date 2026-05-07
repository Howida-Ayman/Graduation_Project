<?php

use App\Http\Controllers\Api\Admin\AcademicYearsController;
use App\Http\Controllers\Api\Admin\DefenseCommitteeController;
use App\Http\Controllers\Api\Admin\DepartmentController;
use App\Http\Controllers\Api\Admin\DoctorController;
use App\Http\Controllers\Api\Admin\MilestoneController;
use App\Http\Controllers\Api\Admin\ProjectRuleController;
use App\Http\Controllers\Api\Admin\ProposalController;
use App\Http\Controllers\Api\Admin\StudentController;
use App\Http\Controllers\Api\Admin\TAController;
use App\Http\Controllers\Api\Admin\TeamController ;
use App\Http\Controllers\Api\MilestoneCommitteeController;
use App\Http\Controllers\Api\SuggestedProject\SuggestedProjectController;
use App\Http\Controllers\Api\User\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware(['auth:sanctum','admin'])->group(function(){

Route::prefix('admin')->group(function(){
    //show ta / dr
    Route::get('/staff/{id}', [DoctorController::class, 'showStaff'])
    ->whereNumber('id');
    //import & export doctors
    Route::prefix('doctor')->group(function(){
    Route::get('/',[DoctorController::class,'index'])->name('doctor.index');
    Route::post('/import',[DoctorController::class,'import'])->name('doctor.import');
    Route::get('/export',[DoctorController::class,'export'])->name('doctor.export');
    Route::post('/store',[DoctorController::class,'store'])->name(name: 'doctor.store'); 
    Route::post('/deactivate-all',[DoctorController::class,'deactivateAllDoctors']);
    });

    //import & export TA
    Route::prefix('TA')->group(function(){
    Route::get('/',[TAController::class,'index'])->name('TA.index');
    Route::post('/import',[TAController::class,'import'])->name('TA.import');
    Route::get('/export',[TAController::class,'export'])->name('TA.export');
    Route::post('/store',[TAController::class,'store'])->name(name: 'TA.store');
    Route::put('/{id}/update',[TAController::class,'update'])->name('TA.update'); 
    Route::post('/deactivate-all',[TAController::class,'deactivateAllTAs']);
    });

    //import & export students
    Route::prefix('student')->group(function(){
    Route::get('/',[StudentController::class,'index'])->name('student.index');
    Route::post('/import',[StudentController::class,'import'])->name('student.import');
    Route::get('/export',[StudentController::class,'export'])->name('student.export');
    Route::post('/store',[StudentController::class,'store'])->name(name: 'student.store');
    Route::put('/{id}/update',[StudentController::class,'update'])->name('student.update'); 
    Route::post('/deactivate-all',[StudentController::class,'deactivateAllStudents']);
    Route::get('/{id}', [StudentController::class, 'show'])->name('student.show');
    });
    //deactivate user(student or doctor or TA)
    Route::post('/{id}/user/toggle-status',[UserController::class,'toggleUserStatus']);

    //Departments
    Route::prefix('departments')->group(function(){
        Route::post('/',[DepartmentController::class,'store'])->name('departments.store');
        Route::put('/{id}/update',[DepartmentController::class,'update'])->name('departments.update');
    });

    //ِAcademic Years
        Route::prefix('academic-years')->group(function(){
        Route::post('/',[AcademicYearsController::class,'store'])->name('academic-years.store');
        Route::put('/{id}/update',[AcademicYearsController::class,'update'])->name('academic-years.update');
        Route::put('/{id}/activate',[AcademicYearsController::class,'setActive'])->name('academic-years.activate');
    });

    //team & project rules
    Route::prefix('project_rules')->group(function(){
        Route::put('/team_rules',[ProjectRuleController::class,'UpdateTeamRules']);
        Route::put('/grading', [ProjectRuleController::class, 'updateGradingRules']);
        Route::post('/{section}',[ProjectRuleController::class,'storeRule']);
        Route::delete('/{id}/delete',[ProjectRuleController::class,'deleteRule']);
    });

    //suggested projects
    Route::prefix('suggested_project')->group(function(){
        Route::post('store',[SuggestedProjectController::class,'store']);
        Route::put('{id}/update',action: [SuggestedProjectController::class,'update']);
        Route::delete('{id}/delete',action: [SuggestedProjectController::class,'destroy']);
    });
    //milestones 
    Route::prefix('milestones')->group(function(){
        Route::post('/',[MilestoneController::class,'store']);
        Route::post('/{id}/toggle-Open-Close',[MilestoneController::class,'toggleOpenClose']);
        Route::put('/{id}/update',[MilestoneController::class,'update']);
        Route::post('/{id}/toggle-active',[MilestoneController::class,'toggleActive']);
        Route::put('/{id}/add/notes',[MilestoneController::class,'storeNote']);
    });

        // milestone commiteess
        Route::prefix('milestone-committees')->group(function () {
        Route::get('/', [MilestoneCommitteeController::class, 'index']);
        Route::get('/eligible-teams', [MilestoneCommitteeController::class, 'eligibleTeams']);
        Route::get('/{teamId}/form-data', [MilestoneCommitteeController::class, 'formData']);
        Route::post('/', [MilestoneCommitteeController::class, 'store']);
        Route::post('/grades/admin-save', [MilestoneCommitteeController::class, 'saveGradeByAdmin']);
        Route::put('/{id}/update', [MilestoneCommitteeController::class, 'update']);
    });

    //teams
    Route::prefix('teams')->group(function(){
        Route::get('/{milestone_id?}',[TeamController::class,'allTeams']);
        Route::get('/view_team/{id}',[TeamController::class,'viewTeam']);
    });
    //final Discussion Commitee
    Route::prefix('defense-committees')->group(function () {
       Route::get('/projects', [DefenseCommitteeController::class, 'projects']);
       Route::get('/options', [DefenseCommitteeController::class, 'committeeOptions']);
       Route::post('/', [DefenseCommitteeController::class, 'store']);
       Route::get('/', [DefenseCommitteeController::class, 'index']);
       Route::put('/{id}', [DefenseCommitteeController::class, 'update']);
       Route::delete('/{id}', [DefenseCommitteeController::class, 'destroy']);

});

    //proposal
    Route::put('/proposal/{id}/{status}',[ProposalController::class,'requestStatus']);
});
});
// get milestones by status
Route::get('milestones/{status}',[MilestoneController::class,'milestonesByStatus'])->middleware(['auth:sanctum']);