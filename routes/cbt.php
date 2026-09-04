<?php

use App\Http\Controllers\Cbt\AnswerController;
use App\Http\Controllers\Cbt\ExamController;
use Illuminate\Support\Facades\Route;

// All routes here already run behind ['web', 'auth', 'role:siswa'] — see
// bootstrap/app.php. Every {siswaUjian} route is additionally guarded by
// SiswaUjianPolicy::view inside the controllers, so a student can't reach
// another student's attempt by editing the URL.

Route::get('dashboard', [ExamController::class, 'dashboard'])->name('dashboard');

Route::post('ujian/{siswaUjian}/mulai', [ExamController::class, 'mulai'])->name('ujian.mulai');
Route::get('ujian/{siswaUjian}', [ExamController::class, 'kerjakan'])->name('ujian.kerjakan');
Route::get('ujian/{siswaUjian}/hasil', [ExamController::class, 'hasil'])->name('ujian.hasil');

Route::post('ujian/{siswaUjian}/jawab', [AnswerController::class, 'simpan'])->name('ujian.jawab');
Route::post('ujian/{siswaUjian}/submit', [AnswerController::class, 'submit'])->name('ujian.submit');
