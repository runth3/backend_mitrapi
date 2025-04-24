<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\OfficeController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\PerformanceController;
use Illuminate\Support\Facades\Route;

// Public routes (no authentication)
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->name('auth.login');
    Route::post('refresh-token', [AuthController::class, 'refresh'])->name('auth.refresh-token');
    Route::post('validate-token', [AuthController::class, 'validateToken'])->name('auth.validate-token'); // Ubah ke POST
});

Route::prefix('news')->group(function () {
    Route::get('latest', [NewsController::class, 'latest'])->name('news.latest');
    Route::get('{id}', [NewsController::class, 'show'])->name('news.show');
});

// Authenticated routes
Route::middleware('api.auth')->group(function () {
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::post('change-password', [AuthController::class, 'changePassword'])->name('auth.change-password');
    });

    // Profile routes
    Route::prefix('profile')->group(function () {
        Route::get('me', [ProfileController::class, 'me'])->name('profile.me');
        Route::get('apps', [ProfileController::class, 'apps'])->name('profile.apps');
    });

    // Office routes
    Route::prefix('offices')->group(function () {
        Route::get('me', [OfficeController::class, 'me'])->name('offices.me');
        Route::get('{instansi_id}', [OfficeController::class, 'showByInstansi'])->name('offices.show-by-instansi');
        Route::get('{instansi_id}/coordinates', [OfficeController::class, 'getKoordinatByInstansi'])->name('offices.coordinates');
    });

    // Attendance routes
    Route::prefix('attendances')->group(function () {
        Route::get('me', [AttendanceController::class, 'me'])->name('attendances.me');
        Route::post('check-in', [AttendanceController::class, 'checkin'])->name('attendances.check-in');
        Route::post('manual-check-in', [AttendanceController::class, 'manualCheckin'])->name('attendances.manual-check-in');
        Route::post('{id}/approve-or-reject', [AttendanceController::class, 'approveOrReject'])->name('attendances.approve-or-reject');
        Route::get('manual', [AttendanceController::class, 'listManualAttendance'])->name('attendances.manual-list');
        Route::post('upload-photo', [AttendanceController::class, 'uploadPhoto'])->name('attendances.upload-photo');
    });

    // Performance routes
    Route::prefix('performances')->group(function () {
        Route::get('/', [PerformanceController::class, 'index'])->name('performances.index');
        Route::get('me', [PerformanceController::class, 'me'])->name('performances.me');
        Route::post('filter', [PerformanceController::class, 'filterByApv'])->name('performances.filter');
        Route::post('/', [PerformanceController::class, 'store'])->name('performances.store');
        Route::get('{id}', [PerformanceController::class, 'show'])->name('performances.show');
        Route::put('{id}', [PerformanceController::class, 'update'])->name('performances.update');
        Route::delete('{id}', [PerformanceController::class, 'destroy'])->name('performances.destroy');
    });

    // Admin-only routes
    Route::middleware('admin')->group(function () {
        Route::prefix('news')->group(function () {
            Route::get('/', [NewsController::class, 'index'])->name('news.index');
            Route::post('/', [NewsController::class, 'store'])->name('news.store');
            Route::put('{id}', [NewsController::class, 'update'])->name('news.update');
            Route::delete('{id}', [NewsController::class, 'destroy'])->name('news.destroy');
        });
    });
});