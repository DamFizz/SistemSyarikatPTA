<?php

use App\Http\Controllers\Employee\AttendanceController;
use App\Http\Controllers\Employee\DashboardController;
use App\Http\Controllers\Employee\LeaveController;
use App\Http\Controllers\Employee\OvertimeController;
use App\Http\Controllers\Employee\PayslipController;
use App\Http\Controllers\Employee\TicketController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])
    ->prefix('employee')
    ->name('employee.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::get('/attendance/status', [AttendanceController::class, 'status'])->middleware('throttle:attendance-status')->name('attendance.status');
        Route::middleware('throttle:attendance')->group(function () {
            Route::post('/attendance/begin', [AttendanceController::class, 'begin'])->name('attendance.begin');
            Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance.clock-in');
            Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('attendance.clock-out');
        });

        Route::resource('overtime', OvertimeController::class)->only(['index', 'create', 'store']);
        Route::resource('leave', LeaveController::class)->only(['index', 'create', 'store']);
        Route::get('/payslips', [PayslipController::class, 'index'])->name('payslips.index');

        Route::resource('tickets', TicketController::class)->only(['index', 'create', 'store', 'show']);
        Route::post('/tickets/{ticket}/reply', [TicketController::class, 'reply'])->name('tickets.reply');
    });
