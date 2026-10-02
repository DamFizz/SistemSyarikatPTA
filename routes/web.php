<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\AttendanceSelfieController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NetworkProbeController;
use App\Http\Controllers\PayslipController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfilePhotoController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/network-probe', NetworkProbeController::class)->middleware('throttle:30,1')->name('network-probe');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/profile/photo', [ProfilePhotoController::class, 'update'])->middleware('throttle:10,1')->name('profile.photo.update');
    Route::delete('/profile/photo', [ProfilePhotoController::class, 'destroy'])->name('profile.photo.destroy');
    Route::get('/avatars/{user}', [ProfilePhotoController::class, 'show'])->name('avatars.show');

    Route::get('/attendance/{attendance}/selfie/{type}', [AttendanceSelfieController::class, 'show'])
        ->whereIn('type', ['in', 'out'])
        ->name('attendance.selfie');

    Route::get('/attachments/{type}/{id}', [AttachmentController::class, 'show'])
        ->whereIn('type', array_keys(AttachmentController::TYPES))
        ->whereNumber('id')
        ->name('attachments.show');
    Route::get('/payslips/{payroll}/download', [PayslipController::class, 'download'])->name('payslips.download');

    Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
    Route::post('/announcements/dismiss', [AnnouncementController::class, 'dismiss'])->name('announcements.dismiss');
    Route::get('/announcements/create', [AnnouncementController::class, 'create'])->name('announcements.create');
    Route::post('/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
    Route::delete('/announcements/bulk', [AnnouncementController::class, 'bulkDestroy'])->name('announcements.bulk-destroy');
    Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');
});

require __DIR__.'/auth.php';
require __DIR__.'/modules/superadmin.php';
require __DIR__.'/modules/hradmin.php';
require __DIR__.'/modules/manager.php';
require __DIR__.'/modules/technician.php';
require __DIR__.'/modules/employee.php';
