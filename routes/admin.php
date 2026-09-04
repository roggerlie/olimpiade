<?php

use App\Exports\LeaderboardExport;
use App\Exports\PesertaImportTemplateExport;
use App\Exports\PesertaUjianExport;
use App\Models\BankSoal;
use App\Models\Peserta;
use App\Models\Ujian;
use App\Services\LeaderboardService;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Facades\Excel;

// All routes here already run behind ['web', 'auth', 'role:admin'] and the
// 'admin.' name / 'admin' path prefix — see bootstrap/app.php.

Route::view('dashboard', 'admin.dashboard')->name('dashboard');
Route::view('master', 'admin.master')->name('master');

Route::view('bank-soal', 'admin.bank-soal.index')->name('bank-soal.index');
Route::get('bank-soal/{bankSoal}/soal', function (BankSoal $bankSoal) {
    return view('admin.bank-soal.soal', ['bankSoal' => $bankSoal]);
})->name('bank-soal.soal');

Route::view('ujian', 'admin.ujian.index')->name('ujian.index');
Route::get('ujian/{ujian}/peserta', function (Ujian $ujian) {
    return view('admin.ujian.peserta', ['ujian' => $ujian]);
})->name('ujian.peserta');
Route::get('ujian/{ujian}/peserta/export', function (Ujian $ujian) {
    return Excel::download(new PesertaUjianExport($ujian->id), "Nilai-{$ujian->nama}.xlsx");
})->name('ujian.peserta.export');

Route::get('ujian/{ujian}/leaderboard', function (Ujian $ujian, LeaderboardService $leaderboard) {
    $ujian->load(['jenjang', 'pelajaran']);

    return view('admin.ujian.leaderboard', [
        'ujian' => $ujian,
        'ranking' => $leaderboard->ranking($ujian),
    ]);
})->name('ujian.leaderboard');
Route::get('ujian/{ujian}/leaderboard/export', function (Ujian $ujian) {
    return Excel::download(new LeaderboardExport($ujian), "Leaderboard-{$ujian->nama}.xlsx");
})->name('ujian.leaderboard.export');

Route::view('peserta', 'admin.peserta.index')->name('peserta.index');
Route::get('peserta/template', function () {
    return Excel::download(new PesertaImportTemplateExport, 'template-peserta.xlsx');
})->name('peserta.template');

Route::view('kartu-peserta', 'admin.kartu-peserta.index')->name('kartu-peserta.index');
Route::get('kartu-peserta/cetak', function () {
    $jenjangId = request('jenjang');

    $peserta = Peserta::query()
        ->with('jenjang')
        ->when($jenjangId, fn ($query) => $query->where('jenjang_id', $jenjangId))
        ->orderBy('nama')
        ->get();

    return view('admin.kartu-peserta.cetak', ['peserta' => $peserta]);
})->name('kartu-peserta.cetak');
