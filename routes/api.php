<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\ProfileController; 
use App\Http\Controllers\AttendanceController;
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Public routes 
Route::post('auth/login', [AuthController::class, 'login'])->name('login');
// Protected routes
Route::middleware('auth:sanctum')->group(function () {
   
    Route::post('auth/logout', [AuthController::class, 'logout']);
    
    // News routes
    Route::apiResource('news', NewsController::class);
    
    // Profile routes
    Route::get('profile/me', [ProfileController::class, 'me']);

    Route::get('/attendance', [AttendanceController::class, 'index']);
    Route::get('/attendance/paginate', [AttendanceController::class, 'indexPaginate']);
    Route::get('/version/check', [VersionController::class, 'checkVersion']); // Ganti ini

});
