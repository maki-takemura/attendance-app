<?php

use App\Http\Controllers\AdminApplicationController;
use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\AdminStaffController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\AttendanceController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest:web')->group(function () {
    Route::get('/admin/login', function () {
        return view('admin.admin-login');
    })->name('admin.login');

    Route::post('/admin/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:login')
        ->name('admin.login.store');
});

Route::post('/admin/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware(['auth:web', 'admin'])
    ->name('admin.logout');

Route::get('/admin/attendance/list', [AdminAttendanceController::class, 'index'])
    ->middleware(['auth:web', 'admin']);

Route::get('/stamp_correction_request/list', [ApplicationController::class, 'index'])
    ->middleware('auth:web');

Route::get('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminApplicationController::class, 'show'])
    ->middleware(['auth:web', 'admin']);

Route::post('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminApplicationController::class, 'approve'])
    ->middleware(['auth:web', 'admin']);

Route::get('/admin/staff/list', [AdminStaffController::class, 'index'])
    ->middleware(['auth:web', 'admin']);

Route::get('/admin/attendance/staff/{id}', [AdminStaffController::class, 'showAttendance'])
    ->middleware(['auth:web', 'admin']);

Route::middleware('auth')->group(function () {
    Route::get('/application/{id}', [ApplicationController::class, 'show']);
    Route::get('/attendance', [AttendanceController::class, 'index']);
    Route::post('/attendance', [AttendanceController::class, 'store']);
    Route::get('/attendance/list', [AttendanceController::class, 'list']);
    Route::get('/attendance/{id}', [AttendanceController::class, 'show']);
    Route::post('/attendance/{id}', [AttendanceController::class, 'update']);
});
