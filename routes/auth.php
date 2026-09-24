<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\GoogleAuthController;
use Illuminate\Support\Facades\Route;

// Split by guard (not a single shared `guest` group): plain `guest`/`auth`
// only ever look at the default `web` guard, so an already-logged-in
// peserta revisiting /login would sail straight past an unguarded `guest`
// check instead of being bounced to their dashboard. See config/auth.php.
Route::middleware('guest:peserta')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'createPeserta'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'storePeserta'])->middleware('throttle:5,1');
});

Route::middleware('guest:web')->group(function () {
    Route::get('admin/login', [AuthenticatedSessionController::class, 'createAdmin'])->name('admin.login');
    Route::post('admin/login', [AuthenticatedSessionController::class, 'storeAdmin'])->middleware('throttle:5,1');

    Route::get('auth/google', [GoogleAuthController::class, 'redirect'])->name('admin.login.google');
    Route::get('auth/google/callback', [GoogleAuthController::class, 'callback'])->name('admin.login.google.callback');
});

Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth:web,peserta')
    ->name('logout');
