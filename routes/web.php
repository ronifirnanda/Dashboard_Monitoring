<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

Route::get('/', [DashboardController::class, 'overview'])->name('overview');
Route::get('/overview', [DashboardController::class, 'overview'])->name('overview.alt');
Route::get('/keutata', [DashboardController::class, 'keutata'])->name('keutata');
