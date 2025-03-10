<?php

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\HydraController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\WorkerController;
use App\Http\Controllers\UserRoleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::prefix('v1')->group(function (){
    Route::post('login', [AuthController::class, 'login']);

});

Route::prefix('v1')->group(function () {

    Route::post('attendances', [AttendanceController::class, 'index']);
    Route::post('attendances/store', [AttendanceController::class, 'store']);
    Route::get('attendances/export', [AttendanceController::class, 'exportAttendance']);
    Route::post('attendances/store-excel', [AttendanceController::class, 'storeByExcel']);
    // Route::post('attendances/detail', [AttendanceController::class, 'show']);
    Route::post('attendances/detail', [AttendanceController::class, 'getAttendance']);
    Route::post('attendances/update', [AttendanceController::class, 'updateAttendance']);
    Route::post('attendances/show', [AttendanceController::class, 'show']);
    Route::post('attendances/summary-by-month', [AttendanceController::class, 'summaryAttendanceByMonth']);
    Route::delete('attendances/{id}', [AttendanceController::class, 'destroy']);

    Route::post('projects', [ProjectController::class, 'index']);
    Route::post('projects/store', [ProjectController::class, 'store']);
    Route::post('projects/update', [ProjectController::class, 'update']);
    Route::delete('/projects/{id}', [ProjectController::class, 'destroy']);
    Route::post('projects/dropdown', [ProjectController::class, 'dropdown']);


    Route::post('workers', [WorkerController::class, 'index']);
    Route::post('workers/store', [WorkerController::class, 'store']);
    Route::post('workers/detail', [WorkerController::class, 'detail']);
    Route::post('workers/update', [WorkerController::class, 'update']);
    Route::delete('/workers/{id}', [WorkerController::class, 'destroy']);
    Route::post('workers/check-nik', [WorkerController::class, 'checkNikExists']);
    Route::delete('workers/{id}', [WorkerController::class, 'destroy']);

});


// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();

// });



Route::prefix('v1')->group(function (){


    Route::post('users', [UserController::class, 'store']);
    Route::apiResource('users', UserController::class)->except(['edit', 'create', 'store', 'update'])->middleware(['auth:sanctum', 'ability:admin,super-admin']);
    Route::put('users/{user}', [UserController::class, 'update'])->middleware(['auth:sanctum', 'ability:admin,super-admin,user']);
    Route::apiResource('roles', RoleController::class)->except(['create', 'edit'])->middleware(['auth:sanctum', 'ability:admin,super-admin,user']);
    Route::apiResource('users.roles', UserRoleController::class)->except(['create', 'edit', 'show', 'update'])->middleware(['auth:sanctum', 'ability:admin,super-admin']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('me', [AuthController::class, 'me'])->middleware('auth:sanctum');

});
