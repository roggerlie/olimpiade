<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect(Auth::user()->hasAnyRole(['administrator', 'admin', 'operator']) ? route('admin.dashboard') : route('cbt.dashboard'));
    }

    return redirect()->route('login');
});
