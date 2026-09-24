<?php

use App\Http\Controllers\Cbt\AnswerController;
use App\Http\Controllers\Cbt\ExamController;
use Illuminate\Support\Facades\Route;

// All routes here already run behind ['web', 'auth:peserta'] — see
// bootstrap/app.php. Every {pesertaUjian} route is additionally guarded by
// PesertaUjianPolicy::view inside the controllers, so a student can't reach
// another student's attempt by editing the URL.

Route::get('dashboard', [ExamController::class, 'dashboard'])->name('dashboard');

Route::get('ujian/{pesertaUjian}/petunjuk', [ExamController::class, 'petunjuk'])->name('ujian.petunjuk');
Route::post('ujian/{pesertaUjian}/mulai', [ExamController::class, 'mulai'])->name('ujian.mulai');
Route::get('ujian/{pesertaUjian}', [ExamController::class, 'kerjakan'])->name('ujian.kerjakan');
Route::get('ujian/{pesertaUjian}/hasil', [ExamController::class, 'hasil'])->name('ujian.hasil');

Route::post('ujian/{pesertaUjian}/jawab', [AnswerController::class, 'simpan'])->name('ujian.jawab');
Route::post('ujian/{pesertaUjian}/submit', [AnswerController::class, 'submit'])->name('ujian.submit');
