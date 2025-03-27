<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\VersionController;

// Public routes
Route::post('auth/login', [AuthController::class, 'login'])->name('login');

// Protected routes
Route::middleware('api.auth')->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::apiResource('news', NewsController::class);
    Route::get('profile/me', [ProfileController::class, 'me']);
    Route::get('/attendance', [AttendanceController::class, 'index']);
    Route::get('/attendance/paginate', [AttendanceController::class, 'indexPaginate']);
    Route::get('/version/check', [VersionController::class, 'checkVersion']);
});