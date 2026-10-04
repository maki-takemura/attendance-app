<?php

use App\Http\Controllers\Admin\AdminAttendanceController;
use App\Http\Controllers\ApplicationListController;
use App\Http\Controllers\AttendanceDetailController;
use App\Http\Controllers\Auth\AdminLoginController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\User\ApplicationController;
use App\Http\Controllers\User\AttendanceController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::post('/register', [RegisterController::class, 'store']);

    Route::post('/login', [LoginController::class, 'store'])
        ->middleware('throttle:login');
});

Route::middleware('auth')->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'create']);
    Route::post('/attendance', [AttendanceController::class, 'store']);
    Route::get('/attendance/list', [AttendanceController::class, 'index']);

    Route::middleware('user.type')->group(function () {
        Route::get('/attendance/{id}', [AttendanceDetailController::class, 'show']);
        Route::post('/attendance/{id}', [AttendanceDetailController::class, 'update']);
        Route::get('/stamp_correction_request/list', [ApplicationListController::class, 'index']);
    });

    Route::get('/application/{application_id}', [ApplicationController::class, 'show']);
});

Route::prefix('admin')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AdminLoginController::class, 'create']);

        Route::post('/login', [AdminLoginController::class, 'store'])
            ->middleware('throttle:login');
    });

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('/attendance/list', [AdminAttendanceController::class, 'index']);
    });

    Route::middleware('auth')->group(function () {
        Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);
    });
});
