<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ProfileController;

Route::redirect('/login', '/admin/login')->name('login');
Route::redirect('/admin', '/admin/login');

// Public entry redirects to admin login first
Route::redirect('/', '/admin/login');
Route::get('/overview', [DashboardController::class, 'overview'])->name('overview');
Route::get('/keutata', [DashboardController::class, 'keutata'])->name('keutata');
Route::get('/archive', [DashboardController::class, 'archive'])->name('archive');
Route::get('/archive/load/{id}', [DashboardController::class, 'archiveLoad'])->name('archive.load');
Route::get('/archive/download/{id}', [DashboardController::class, 'archiveDownload'])->name('archive.download');
Route::post('/admin/keutata/import', [DashboardController::class, 'importKeutata'])->name('keutata.import');
Route::post('/admin/keutata/sync-google-sheet', [DashboardController::class, 'syncGoogleSheet'])->name('keutata.sync-google-sheet');

// Profile (authenticated users)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

// Admin area
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.post');

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
        Route::delete('/archive/{id}', [DashboardController::class, 'archiveDelete'])->name('archive.delete');
        Route::post('/archive/bulk-delete', [DashboardController::class, 'archiveBulkDelete'])->name('archive.bulk-delete');
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings/google-sheets', [SettingController::class, 'updateGoogleSheets'])->name('settings.update-google-sheets');
    });
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::post('/admin/keutata/cell', [DashboardController::class, 'updateKeutataCell'])->name('keutata.update-cell');
    Route::get('/admin/keutata/fragment', [DashboardController::class, 'keutataFragment'])->name('keutata.fragment');
});

