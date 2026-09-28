<?php

use App\Http\Controllers\Manager\DashboardController;
use App\Http\Controllers\Manager\EmployeeController;
use App\Http\Controllers\Manager\LeaveController;
use App\Http\Controllers\Manager\OvertimeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:manager'])
    ->prefix('manager')
    ->name('manager.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::resource('employees', EmployeeController::class)->only(['index', 'create', 'store']);

        Route::get('/overtime', [OvertimeController::class, 'index'])->name('overtime.index');
        Route::post('/overtime/{overtime}/approve', [OvertimeController::class, 'approve'])->name('overtime.approve');
        Route::post('/overtime/{overtime}/reject', [OvertimeController::class, 'reject'])->name('overtime.reject');

        Route::get('/leave', [LeaveController::class, 'index'])->name('leave.index');
        Route::post('/leave/{leaveRequest}/approve', [LeaveController::class, 'approve'])->name('leave.approve');
        Route::post('/leave/{leaveRequest}/reject', [LeaveController::class, 'reject'])->name('leave.reject');

        // Routes for department attendance, announcements added in later phases.
    });
