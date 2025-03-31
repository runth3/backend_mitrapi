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

// Public routes
Route::post('auth/login', [AuthController::class, 'login'])->name('login');

// Protected routes
Route::middleware('api.auth')->group(function () {
    // Autentikasi
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/changepassword', [AuthController::class, 'changePassword']);

    // Profil Pengguna
    Route::get('/profile/me', [ProfileController::class, 'me']);
    Route::get('/profile/apps', [ProfileController::class, 'apps']);

    // Data Kantor
    Route::get('/office/me', [OfficeController::class, 'me']);

    // Absensi
    Route::get('/attendance/me', [AttendanceController::class, 'me']); // Get current month's attendance
    Route::post('/attendance/checkin', [AttendanceController::class, 'checkin']); // Automatic check-in
    Route::post('/attendance/manual-checkin', [AttendanceController::class, 'manualCheckin']); // Manual check-in
    Route::post('/attendance/{id}/approve-reject', [AttendanceController::class, 'approveOrReject']); // Approve/Reject manual attendance
    Route::get('/attendance/manual', [AttendanceController::class, 'listManualAttendance']); // List manual attendance by status
    Route::post('/attendance/upload-photo', [AttendanceController::class, 'uploadPhoto']); // Upload attendance photo

    // Berita
    Route::get('/news/latest', [NewsController::class, 'latest']);
    Route::get('/news/{id}', [NewsController::class, 'show']); // Get a specific news item

    // Versi Aplikasi
    Route::get('/version/check', [VersionController::class, 'checkVersion']);

    // Model Wajah
   
    Route::get('/face-models', [FaceModelController::class, 'index']); // List all face models
    Route::post('/face-models', [FaceModelController::class, 'store']); // Upload a new face model
    Route::get('/face-models/active', [FaceModelController::class, 'getActive']); // Get the latest active face model
    Route::get('/face-models/{id}', [FaceModelController::class, 'show']); // Show a specific face model
    Route::put('/face-models/{id}/set-active', [FaceModelController::class, 'setActive']); // Set a face model as active
    Route::delete('/face-models/{id}', [FaceModelController::class, 'destroy']); // Delete a face model
  
    // Push Notification
    // Route::post('/notifications/send', [NotificationController::class, 'send']); // Belum aktif

    // Performances
    Route::get('/performances', [PerformanceController::class, 'index']); // List all performances (not deleted)
    Route::get('/performances/me', [PerformanceController::class, 'me']); // List all my performances
    Route::post('/performances/filter', [PerformanceController::class, 'filterByApv']); // Filter by apv
    Route::post('/performances', [PerformanceController::class, 'store']); // Create a performance
    Route::get('/performances/{id}', [PerformanceController::class, 'show']); // Show a specific performance
    Route::put('/performances/{id}', [PerformanceController::class, 'update']); // Update a performance
    Route::delete('/performances/{id}', [PerformanceController::class, 'destroy']); // Soft delete a performance
});

// Admin-only routes
Route::middleware(['api.auth', 'admin'])->group(function () {
    Route::delete('/face/models/{id}', [FaceModelController::class, 'destroy']); // Admin-only route
     
    Route::post('/news', [NewsController::class, 'store']); // Create news
    Route::put('/news/{id}', [NewsController::class, 'update']); // Update news
    Route::delete('/news/{id}', [NewsController::class, 'destroy']); // Delete news
});