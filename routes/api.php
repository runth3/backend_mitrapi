<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\OfficeController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\PerformanceController;
use App\Http\Controllers\VersionController;
use App\Http\Controllers\FaceModelController;
use App\Http\Controllers\UserController;

// Public routes
Route::post('auth/login', [AuthController::class, 'login'])->name('login');
Route::post('auth/refresh', [AuthController::class, 'refresh'])->name('_REFRESH_TOKEN');
Route::get('/version/check', [VersionController::class, 'checkVersion']);

// Protected routes
Route::middleware('api.auth')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/changepassword', [AuthController::class, 'changePassword']);
    Route::get('/profile/me', [ProfileController::class, 'me']);
    Route::get('/profile/apps', [ProfileController::class, 'apps']);
    Route::get('/office/me', [OfficeController::class, 'me']);
    Route::get('/office/{id_instansi}', [OfficeController::class, 'showByInstansi']);
    Route::get('/office/koordinat/{id_instansi}', [OfficeController::class, 'getKoordinatByInstansi']);
    Route::get('/attendance/me', [AttendanceController::class, 'me']);
    Route::post('/attendance/checkin', [AttendanceController::class, 'checkin']);
    Route::post('/attendance/manual-checkin', [AttendanceController::class, 'manualCheckin']);
    Route::post('/attendance/{id}/approve-reject', [AttendanceController::class, 'approveOrReject']);
    Route::get('/attendance/manual', [AttendanceController::class, 'listManualAttendance']);
    Route::post('/attendance/upload-photo', [AttendanceController::class, 'uploadPhoto']);
    Route::get('/news/latest', [NewsController::class, 'latest']);
    Route::get('/news/{id}', [NewsController::class, 'show']);
    Route::prefix('face-model')->group(function () {
        Route::get('/', [FaceModelController::class, 'index']);
        Route::post('/', [FaceModelController::class, 'store']);
        Route::get('/active', [FaceModelController::class, 'getActive']);
        Route::get('/{id}', [FaceModelController::class, 'show']);
        Route::put('/{id}/set-active', [FaceModelController::class, 'setActive']);
        Route::delete('/{id}', [FaceModelController::class, 'destroy']);
        Route::get('/user/{userId}', [FaceModelController::class, 'getByUserId']);
    });
    Route::get('/performance', [PerformanceController::class, 'index']);
    Route::get('/performance/me', [PerformanceController::class, 'me']);
    Route::post('/performance/filter', [PerformanceController::class, 'filterByApv']);
    Route::post('/performance', [PerformanceController::class, 'store']);
    Route::get('/performance/{id}', [PerformanceController::class, 'show']);
    Route::put('/performance/{id}', [PerformanceController::class, 'update']);
    Route::delete('/performance/{id}', [PerformanceController::class, 'destroy']);
});

// Admin-only routes
Route::middleware(['api.auth', 'admin'])->group(function () {
    Route::delete('/face/models/{id}', [FaceModelController::class, 'destroy']);
    Route::get('/news', [NewsController::class, 'index']);
    Route::post('/news', [NewsController::class, 'store']);
    Route::put('/news/{id}', [NewsController::class, 'update']);
    Route::delete('/news/{id}', [NewsController::class, 'destroy']);
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
});