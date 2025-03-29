<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\OfficeController;
use App\Http\Controllers\AttendanceController; 
use App\Http\Controllers\PerformanceController;
use App\Http\Controllers\VersionController;

// Public routes
Route::post('auth/login', [AuthController::class, 'login'])->name('login');

// Protected routes
Route::middleware('api.auth')->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']); 
    Route::get('profile/me', [ProfileController::class, 'me']);
    Route::get('profile/apps', [ProfileController::class, 'apps']);
    Route::get('office/me', [OfficeController::class, 'me']);
    Route::get('/attendance/me', [AttendanceController::class, 'me']);
    Route::get('/performance/me', [PerformanceController::class, 'me']);
    Route::get('/news/latest', [NewsController::class, 'latest']);
     Route::get('/version/check', [VersionController::class, 'checkVersion']);
});