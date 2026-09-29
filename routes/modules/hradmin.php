<?php

use App\Http\Controllers\HRAdmin\AttendanceController;
use App\Http\Controllers\HRAdmin\DashboardController;
use App\Http\Controllers\HRAdmin\DepartmentController;
use App\Http\Controllers\HRAdmin\EmployeeController;
use App\Http\Controllers\HRAdmin\LeaveController;
use App\Http\Controllers\HRAdmin\OvertimeController;
use App\Http\Controllers\HRAdmin\PayrollController;
use App\Http\Controllers\HRAdmin\ReportController;
use App\Http\Controllers\HRAdmin\TicketController;
use App\Http\Controllers\HRAdmin\WorkHoursController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:hr_admin,super_admin'])
    ->prefix('hr')
    ->name('hr.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::resource('employees', EmployeeController::class)->except(['show', 'destroy']);
        Route::post('/employees/{employee}/reset-device', [EmployeeController::class, 'resetDevice'])->name('employees.reset-device');
        Route::resource('departments', DepartmentController::class)->except(['show', 'destroy']);

        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');

        Route::get('/overtime', [OvertimeController::class, 'index'])->name('overtime.index');
        Route::post('/overtime/{overtime}/approve', [OvertimeController::class, 'approve'])->name('overtime.approve');
        Route::post('/overtime/{overtime}/reject', [OvertimeController::class, 'reject'])->name('overtime.reject');

        Route::get('/leave', [LeaveController::class, 'index'])->name('leave.index');
        Route::post('/leave/{leaveRequest}/approve', [LeaveController::class, 'approve'])->name('leave.approve');
        Route::post('/leave/{leaveRequest}/reject', [LeaveController::class, 'reject'])->name('leave.reject');

        Route::get('/work-hours', [WorkHoursController::class, 'index'])->name('work-hours.index');
        Route::put('/work-hours/settings', [WorkHoursController::class, 'updateSettings'])->name('work-hours.settings');
        Route::put('/work-hours/employees/{employee}', [WorkHoursController::class, 'updateEmployee'])->name('work-hours.employee');
        Route::post('/work-hours/justifications/{justification}/review', [WorkHoursController::class, 'review'])->name('work-hours.review');

        Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
        Route::get('/payroll/create', [PayrollController::class, 'create'])->name('payroll.create');
        Route::post('/payroll', [PayrollController::class, 'store'])->name('payroll.store');
        Route::get('/payroll/{payrollPeriod}', [PayrollController::class, 'show'])->name('payroll.show');
        Route::post('/payroll/{payrollPeriod}/generate', [PayrollController::class, 'generate'])->name('payroll.generate');
        Route::post('/payroll/{payrollPeriod}/approve', [PayrollController::class, 'approve'])->name('payroll.approve');
        Route::post('/payroll/{payrollPeriod}/mark-paid', [PayrollController::class, 'markPaid'])->name('payroll.mark-paid');

        Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
        Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/attendance', [ReportController::class, 'attendance'])->name('reports.attendance');
        Route::get('/reports/leave', [ReportController::class, 'leave'])->name('reports.leave');
        Route::get('/reports/overtime', [ReportController::class, 'overtime'])->name('reports.overtime');
        Route::get('/reports/payroll', [ReportController::class, 'payroll'])->name('reports.payroll');
        Route::get('/reports/helpdesk', [ReportController::class, 'helpdesk'])->name('reports.helpdesk');
    });
