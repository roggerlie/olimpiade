<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect(Auth::user()->hasRole('admin') ? route('admin.dashboard') : route('cbt.dashboard'));
    }

    return redirect()->route('login');
});
