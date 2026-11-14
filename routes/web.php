<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DefaultIndicatorsController;
use App\Http\Controllers\IndicatorController;
use App\Http\Controllers\IndicatorSynchronizationController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/indicators/restore', [DefaultIndicatorsController::class, 'store'])
        ->name('indicators.restore');
    Route::patch('/indicators/{indicator}', [IndicatorController::class, 'update'])
        ->name('indicators.update');
    Route::delete('/indicators/{indicator}', [IndicatorController::class, 'destroy'])
        ->name('indicators.destroy');
    Route::post('/indicators/{indicator}/synchronize', [IndicatorSynchronizationController::class, 'store'])
        ->name('indicators.synchronize');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
