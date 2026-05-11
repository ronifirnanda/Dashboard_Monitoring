<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\LoginController;

Route::redirect('/login', '/admin/login');
Route::redirect('/admin', '/admin/login');

// Public dashboard routes for everyone
Route::get('/', [DashboardController::class, 'overview'])->name('overview');
Route::get('/overview', [DashboardController::class, 'overview'])->name('overview.alt');
Route::get('/keutata', [DashboardController::class, 'keutata'])->name('keutata');
Route::get('/archive', [DashboardController::class, 'archive'])->name('archive');
Route::get('/archive/load/{id}', [DashboardController::class, 'archiveLoad'])->name('archive.load');

// Admin area
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.post');

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
        Route::post('/keutata/import', [DashboardController::class, 'importKeutata'])->name('keutata.import');
        Route::post('/keutata/sync-google-sheet', [DashboardController::class, 'syncGoogleSheet'])->name('keutata.sync-google-sheet');
        Route::delete('/archive/{id}', [DashboardController::class, 'archiveDelete'])->name('archive.delete');
        Route::post('/archive/bulk-delete', [DashboardController::class, 'archiveBulkDelete'])->name('archive.bulk-delete');
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings/google-sheets', [SettingController::class, 'updateGoogleSheets'])->name('settings.update-google-sheets');
    });
});

