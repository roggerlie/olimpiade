<?php

use App\Exports\LeaderboardExport;
use App\Exports\PesertaExport;
use App\Exports\PesertaImportTemplateExport;
use App\Exports\PesertaUjianExport;
use App\Exports\SoalImportTemplateExport;
use App\Exports\SoalWordTemplateExport;
use App\Http\Controllers\Admin\SoalGambarUploadController;
use App\Models\BankSoal;
use App\Models\Jenjang;
use App\Models\Pelajaran;
use App\Models\Peserta;
use App\Models\Soal;
use App\Models\Ujian;
use App\Services\LeaderboardService;
use App\Services\ResetDataService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

// All routes here already run behind ['web', 'auth', 'role:administrator|admin|operator']
// and the 'admin.' name / 'admin' path prefix — see bootstrap/app.php. That
// only gates entry to the whole /admin area; the `permission:` middleware
// groups below narrow what each of the three tiers can actually reach beyond
// the dashboard. `administrator` bypasses every one of them (Gate::before in
// AppServiceProvider), so these groups only matter for `admin` and `operator`.

// Bare /admin has no page of its own — send it to the dashboard.
Route::get('/', fn () => redirect()->route('admin.dashboard'))->name('home');
Route::view('dashboard', 'admin.dashboard')->name('dashboard');

Route::middleware('permission:master-data.manage')->group(function (): void {
    Route::view('master', 'admin.master')->name('master');
});

Route::middleware('permission:bank-soal.manage')->group(function (): void {
    Route::view('bank-soal', 'admin.bank-soal.index')->name('bank-soal.index');
    Route::get('bank-soal/{bankSoal}/soal', function (BankSoal $bankSoal) {
        return view('admin.bank-soal.soal', ['bankSoal' => $bankSoal->load(['jenjang', 'pelajaran'])]);
    })->name('bank-soal.soal');
    // Tambah/Ubah Soal are their own pages (not a modal on the list above)
    // — the six TinyMCE editors need real room. One Livewire component
    // (admin.soal.form) serves both; `soal` route-model-binds only for edit.
    Route::get('bank-soal/{bankSoal}/soal/create', function (BankSoal $bankSoal) {
        return view('admin.bank-soal.soal-create', ['bankSoal' => $bankSoal]);
    })->name('bank-soal.soal.create');
    Route::get('bank-soal/{bankSoal}/soal/{soal}/edit', function (BankSoal $bankSoal, Soal $soal) {
        abort_unless($soal->bank_soal_id === $bankSoal->id, 404);

        return view('admin.bank-soal.soal-edit', ['bankSoal' => $bankSoal, 'soal' => $soal]);
    })->name('bank-soal.soal.edit');
    Route::get('bank-soal/{bankSoal}/soal/template', function (BankSoal $bankSoal) {
        return Excel::download(new SoalImportTemplateExport, "template-soal-{$bankSoal->nama}.xlsx");
    })->name('bank-soal.soal.template');
    Route::post('bank-soal/{bankSoal}/soal/upload-gambar', [SoalGambarUploadController::class, 'store'])
        ->name('bank-soal.soal.upload-gambar');
    Route::get('bank-soal/{bankSoal}/soal/template-word', function (BankSoal $bankSoal) {
        $path = tempnam(sys_get_temp_dir(), 'soal-template').'.docx';
        (new SoalWordTemplateExport)->build()->save($path, 'Word2007');

        return response()->download($path, "template-soal-{$bankSoal->nama}.docx")->deleteFileAfterSend();
    })->name('bank-soal.soal.template-word');
});

Route::middleware('permission:ujian.view')->group(function (): void {
    Route::view('ujian', 'admin.ujian.index')->name('ujian.index');
});

Route::middleware('permission:ujian-peserta.manage')->group(function (): void {
    Route::get('ujian/{ujian}/peserta', function (Ujian $ujian) {
        return view('admin.ujian.peserta', ['ujian' => $ujian]);
    })->name('ujian.peserta');
    Route::get('ujian/{ujian}/peserta/export', function (Ujian $ujian) {
        return Excel::download(new PesertaUjianExport($ujian->id), "Nilai-{$ujian->nama}.xlsx");
    })->name('ujian.peserta.export');
});

Route::middleware('permission:leaderboard.view')->group(function (): void {
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
});

Route::middleware('permission:peserta.manage')->group(function (): void {
    Route::view('peserta', 'admin.peserta.index')->name('peserta.index');
    Route::get('peserta/template', function () {
        return Excel::download(new PesertaImportTemplateExport, 'template-peserta.xlsx');
    })->name('peserta.template');
    // Same `q`/`jenjang`/`pelajaran` query params as the Kelola Peserta
    // list's own filters (see the admin.peserta.manager component's #[Url]
    // props), or `ids[]` — the peserta ticked in that list — which takes precedence.
    Route::get('peserta/export', function () {
        $ids = collect(request()->array('ids'))->filter(fn ($id) => ctype_digit((string) $id))->map(fn ($id) => (int) $id)->values()->all();

        if ($ids) {
            return Excel::download(new PesertaExport(ids: $ids), 'Peserta-Terpilih-'.now()->format('Y-m-d').'.xlsx');
        }

        $jenjang = Jenjang::find(request('jenjang'));
        $pelajaran = Pelajaran::find(request('pelajaran'));
        $namaFile = collect(['Peserta', $jenjang?->nama, $pelajaran?->nama, now()->format('Y-m-d')])->filter()->join('-');

        return Excel::download(new PesertaExport(request('q'), $jenjang?->id, $pelajaran?->id), "{$namaFile}.xlsx");
    })->name('peserta.export');
});

Route::middleware('permission:kartu-peserta.print')->group(function (): void {
    Route::view('kartu-peserta', 'admin.kartu-peserta.index')->name('kartu-peserta.index');
    Route::get('kartu-peserta/cetak', function () {
        $peserta = Peserta::query()
            ->with('jenjang')
            ->filterAdmin(null, request('jenjang'), request('pelajaran'))
            ->orderBy('nama')
            ->get();

        return view('admin.kartu-peserta.cetak', ['peserta' => $peserta]);
    })->name('kartu-peserta.cetak');
});

Route::middleware('permission:users.manage')->group(function (): void {
    Route::view('users', 'admin.users.index')->name('users.index');
});

// Deliberately gated by role, not a Spatie permission: who's allowed to
// edit what each role can do must never be something a role could grant
// itself. Only `administrator` (which already bypasses every permission
// check) can reach this.
Route::middleware('role:administrator')->group(function (): void {
    Route::view('roles', 'admin.roles.index')->name('roles.index');

    // Same reasoning, even more so: Reset Data wipes whole categories of
    // data at once (see App\Services\ResetDataService).
    Route::view('pengaturan', 'admin.pengaturan.index')->name('pengaturan.index');
    Route::get('pengaturan/backup/{nama}', function (string $nama) {
        $path = ResetDataService::FOLDER_BACKUP."/{$nama}";
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path);
    })->where('nama', 'reset-\d{8}_\d{6}\.xlsx')->name('pengaturan.backup');
});
