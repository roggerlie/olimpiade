<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'createPeserta'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'storePeserta'])->middleware('throttle:5,1');

    Route::get('admin/login', [AuthenticatedSessionController::class, 'createAdmin'])->name('admin.login');
    Route::post('admin/login', [AuthenticatedSessionController::class, 'storeAdmin'])->middleware('throttle:5,1');
});

Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');
