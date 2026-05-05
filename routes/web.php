<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

Route::get('/', [DashboardController::class, 'overview'])->name('overview');
Route::get('/overview', [DashboardController::class, 'overview'])->name('overview.alt');
Route::get('/keutata', [DashboardController::class, 'keutata'])->name('keutata');
Route::post('/keutata/import', [DashboardController::class, 'importKeutata'])->name('keutata.import');
Route::get('/archive', [DashboardController::class, 'archive'])->name('archive');
Route::get('/archive/load/{id}', [DashboardController::class, 'archiveLoad'])->name('archive.load');
Route::delete('/archive/{id}', [DashboardController::class, 'archiveDelete'])->name('archive.delete');
Route::post('/archive/bulk-delete', [DashboardController::class, 'archiveBulkDelete'])->name('archive.bulk-delete');

