<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\OfficeController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\PerformanceController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\FaceModelController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\ApplicationLetterController;
use App\Http\Controllers\VersionController;
use Illuminate\Support\Facades\Route;



// Public routes (no authentication)
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->name('login')->middleware('throttle:5,1');
    Route::post('login-with-data', [AuthController::class, 'loginWithData'])->middleware('throttle:5,1');
    Route::post('refresh-with-data', [AuthController::class, 'refreshWithData']);
    Route::post('refresh-token', [AuthController::class, 'refresh'])->name('refresh-token');
    Route::get('validate-token', [AuthController::class, 'validateToken'])->name('validate-token');
});

// Public routes
Route::prefix('news')->group(function () {
    Route::get('latest', [NewsController::class, 'latest'])->name('news.latest');
    Route::get('{id}', [NewsController::class, 'show'])->name('news.show');
});

// Version check route
Route::get('version/check', [VersionController::class, 'checkVersion'])
    ->middleware('api.auth')
    ->name('version.check');

// Authenticated routes
Route::middleware('api.auth')->group(function () {
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::post('change-password', [AuthController::class, 'changePassword'])->name('change-password');
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
        Route::post('upload-photo-public', [AttendanceController::class, 'uploadPhotoPublic'])->name('attendances.upload-photo-public');
        Route::get('photos', [AttendanceController::class, 'listPhotos'])->name('attendances.list-photos');
        Route::get('photo/{filePath}', [AttendanceController::class, 'getPhoto'])->name('attendances.get-photo');
    });

    // Calendar routes
    Route::prefix('calendar')->group(function () {
        Route::get('holidays', [CalendarController::class, 'getHolidays'])->name('calendar.holidays');
        Route::get('incidental-days', [CalendarController::class, 'getIncidentalDays'])->name('calendar.incidental-days');
    });

    // Application Letter routes
    Route::prefix('application-letters')->group(function () {
        Route::get('/', [ApplicationLetterController::class, 'index'])->name('application-letters.index');
        Route::post('/', [ApplicationLetterController::class, 'store'])->name('application-letters.store');
        Route::get('check-approval', [ApplicationLetterController::class, 'checkApproval'])->name('application-letters.check-approval');
        Route::get('current-month', [ApplicationLetterController::class, 'listCurrentMonth'])->name('application-letters.current-month');
        Route::get('{id}', [ApplicationLetterController::class, 'show'])->name('application-letters.show');
        Route::put('{id}', [ApplicationLetterController::class, 'update'])->name('application-letters.update');
        Route::delete('{id}', [ApplicationLetterController::class, 'destroy'])->name('application-letters.destroy');
    });

    // Face Models
    Route::prefix('face-models')->group(function () {
        Route::get('/', [FaceModelController::class, 'index'])->name('face-models.index');
        Route::post('/', [FaceModelController::class, 'store'])->name('face-models.store');
        Route::get('active', [FaceModelController::class, 'getActive'])->name('face-models.active')->middleware('throttle:60,1');
        Route::get('{id}', [FaceModelController::class, 'show'])->name('face-models.show')->middleware('throttle:60,1');
        Route::put('{id}/set-active', [FaceModelController::class, 'setActive'])->name('face-models.set-active');
        Route::delete('{id}', [FaceModelController::class, 'destroy'])->name('face-models.destroy');
        Route::get('user/{user_id}', [FaceModelController::class, 'getByUserId'])->name('face-models.by-user');
        Route::get('verify', [FaceModelController::class, 'getActive'])->name('face-models.verify')->middleware('throttle:60,1');
    });

    // Performance
    Route::prefix('performances')->group(function () {
        Route::get('/', [PerformanceController::class, 'index'])->name('performances.index');
        Route::get('me', [PerformanceController::class, 'me'])->name('performances.me');
        Route::get('by-date', [PerformanceController::class, 'getByDate'])->name('performances.by-date');
        Route::post('filter', [PerformanceController::class, 'filterByApv'])->name('performances.filter');
        Route::post('/', [PerformanceController::class, 'store'])->name('performances.store');
        Route::get('{id}', [PerformanceController::class, 'show'])->name('performances.show');
        Route::put('{id}', [PerformanceController::class, 'update'])->name('performances.update');
        Route::delete('{id}', [PerformanceController::class, 'destroy'])->name('performances.destroy');
    });

    // Admin-only routes
    Route::middleware('admin')->group(function () {
        // User management routes   
        Route::prefix('users')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('users.index');
            Route::post('/', [UserController::class, 'store'])->name('users.store');
            Route::get('{id}', [UserController::class, 'show'])->name('users.show');
            Route::put('{id}', [UserController::class, 'update'])->name('users.update');
            Route::delete('{id}', [UserController::class, 'destroy'])->name('users.destroy');
        });
        Route::prefix('attendances')->group(function () {
            Route::get('/', [AttendanceController::class, 'index'])->name('attendances.index');
            Route::get('{id}', [AttendanceController::class, 'show'])->name('attendances.show');
            Route::put('{id}', [AttendanceController::class, 'update'])->name('attendances.update');
            Route::delete('{id}', [AttendanceController::class, 'destroy'])->name('attendances.destroy');
        }); 

        Route::prefix('news')->group(function () {
            Route::get('/', [NewsController::class, 'index'])->name('news.index');
            Route::post('/', [NewsController::class, 'store'])->name('news.store');
            Route::put('{id}', [NewsController::class, 'update'])->name('news.update');
            Route::delete('{id}', [NewsController::class, 'destroy'])->name('news.destroy');
        });
    });
});