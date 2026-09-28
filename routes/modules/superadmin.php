<?php

use App\Http\Controllers\SuperAdmin\AuditLogController;
use App\Http\Controllers\SuperAdmin\DashboardController;
use App\Http\Controllers\SuperAdmin\OfficeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:super_admin'])
    ->prefix('super-admin')
    ->name('super-admin.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

        Route::resource('offices', OfficeController::class)->except(['show', 'destroy']);

        // Routes for shifts, settings added in later phases.
    });
