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
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->name('login');
    Route::post('refresh-token', [AuthController::class, 'refresh'])->name('refresh-token');
});

Route::get('version/check', [VersionController::class, 'checkVersion'])->name('version.check');

// Protected routes (authenticated users)
Route::middleware('api.auth')->group(function () {
    // Authentication
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::post('change-password', [AuthController::class, 'changePassword'])->name('change-password');
    });

    // Profile
    Route::prefix('profile')->group(function () {
        Route::get('me', [ProfileController::class, 'me'])->name('profile.me');
        Route::get('apps', [ProfileController::class, 'apps'])->name('profile.apps');
    });

    // Office
    Route::prefix('offices')->group(function () {
        Route::get('me', [OfficeController::class, 'me'])->name('offices.me');
        Route::get('{instansi_id}', [OfficeController::class, 'showByInstansi'])->name('offices.show-by-instansi');
        Route::get('{instansi_id}/coordinates', [OfficeController::class, 'getKoordinatByInstansi'])->name('offices.coordinates');
    });

    // Attendance
    Route::prefix('attendances')->group(function () {
        Route::get('me', [AttendanceController::class, 'me'])->name('attendances.me');
        Route::post('check-in', [AttendanceController::class, 'checkin'])->name('attendances.check-in');
        Route::post('manual-check-in', [AttendanceController::class, 'manualCheckin'])->name('attendances.manual-check-in');
        Route::post('{id}/approve-or-reject', [AttendanceController::class, 'approveOrReject'])->name('attendances.approve-or-reject');
        Route::get('manual', [AttendanceController::class, 'listManualAttendance'])->name('attendances.manual-list');
        Route::post('upload-photo', [AttendanceController::class, 'uploadPhoto'])->name('attendances.upload-photo');
    });

    // News
    Route::prefix('news')->group(function () {
        Route::get('latest', [NewsController::class, 'latest'])->name('news.latest');
        Route::get('{id}', [NewsController::class, 'show'])->name('news.show');
    });

    // Face Models
    Route::prefix('face-models')->group(function () {
        Route::get('/', [FaceModelController::class, 'index'])->name('face-models.index');
        Route::post('/', [FaceModelController::class, 'store'])->name('face-models.store');
        Route::get('active', [FaceModelController::class, 'getActive'])->name('face-models.active');
        Route::get('{id}', [FaceModelController::class, 'show'])->name('face-models.show');
        Route::put('{id}/set-active', [FaceModelController::class, 'setActive'])->name('face-models.set-active');
        Route::delete('{id}', [FaceModelController::class, 'destroy'])->name('face-models.destroy');
        Route::get('user/{user_id}', [FaceModelController::class, 'getByUserId'])->name('face-models.by-user');
    });

    // Performance
    Route::prefix('performances')->group(function () {
        Route::get('/', [PerformanceController::class, 'index'])->name('performances.index');
        Route::get('me', [PerformanceController::class, 'me'])->name('performances.me');
        Route::post('filter', [PerformanceController::class, 'filterByApv'])->name('performances.filter');
        Route::post('/', [PerformanceController::class, 'store'])->name('performances.store');
        Route::get('{id}', [PerformanceController::class, 'show'])->name('performances.show');
        Route::put('{id}', [PerformanceController::class, 'update'])->name('performances.update');
        Route::delete('{id}', [PerformanceController::class, 'destroy'])->name('performances.destroy');
    });
});

// Admin-only routes
Route::middleware(['api.auth', 'admin'])->group(function () {
    // News (Admin)
    Route::prefix('news')->group(function () {
        Route::get('/', [NewsController::class, 'index'])->name('news.index');
        Route::post('/', [NewsController::class, 'store'])->name('news.store');
        Route::put('{id}', [NewsController::class, 'update'])->name('news.update');
        Route::delete('{id}', [NewsController::class, 'destroy'])->name('news.destroy');
    });

    // Users
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('users.index');
        Route::post('/', [UserController::class, 'store'])->name('users.store');
        Route::get('{id}', [UserController::class, 'show'])->name('users.show');
        Route::put('{id}', [UserController::class, 'update'])->name('users.update');
        Route::delete('{id}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    // Face Models (Admin)
    Route::prefix('face-models')->group(function () {
        Route::delete('{id}', [FaceModelController::class, 'destroy'])->name('face-models.admin-destroy');
    });
});