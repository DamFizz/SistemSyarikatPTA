<?php

use App\Http\Controllers\Technician\DashboardController;
use App\Http\Controllers\Technician\TicketController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:technician'])
    ->prefix('technician')
    ->name('technician.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
        Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
        Route::post('/tickets/{ticket}/assign', [TicketController::class, 'assign'])->name('tickets.assign');
        Route::post('/tickets/{ticket}/status', [TicketController::class, 'updateStatus'])->name('tickets.status');
        Route::post('/tickets/{ticket}/reply', [TicketController::class, 'reply'])->name('tickets.reply');
    });
