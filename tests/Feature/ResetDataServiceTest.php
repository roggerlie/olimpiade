<?php

use App\Enums\KategoriReset;
use App\Exports\BackupSebelumResetExport;
use App\Models\BankSoal;
use App\Models\Jenjang;
use App\Models\Pelajaran;
use App\Models\Peserta;
use App\Models\PesertaSoal;
use App\Models\PesertaUjian;
use App\Models\Soal;
use App\Models\Ujian;
use App\Models\User;
use App\Services\ResetDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');
});

/**
 * One of everything, linked the way real data is: a finished attempt on an
 * ujian whose session is safely in the future (so alasanDitolak() stays quiet).
 */
function isiDataLengkap(): PesertaSoal
{
    $jenjang = Jenjang::factory()->create();
    $pelajaran = Pelajaran::factory()->create();
    $bankSoal = BankSoal::factory()->create(['jenjang_id' => $jenjang->id, 'pelajaran_id' => $pelajaran->id]);
    $soal = Soal::factory()->create(['bank_soal_id' => $bankSoal->id]);
    $ujian = Ujian::factory()->create([
        'bank_soal_id' => $bankSoal->id,
        'jenjang_id' => $jenjang->id,
        'pelajaran_id' => $pelajaran->id,
        'sesi_mulai' => now()->addDay(),
        'sesi_selesai' => now()->addDay()->addHours(2),
    ]);
    $peserta = Peserta::factory()->create(['jenjang_id' => $jenjang->id]);
    $peserta->pelajaranLomba()->attach($pelajaran);
    $attempt = PesertaUjian::factory()->selesai()->create(['peserta_id' => $peserta->id, 'ujian_id' => $ujian->id]);

    return PesertaSoal::factory()->create(['peserta_ujian_id' => $attempt->id, 'soal_id' => $soal->id, 'urutan' => 1]);
}

test('dependencies are resolved transitively, in safe delete order, ignoring unknown values', function () {
    expect(KategoriReset::lengkapi(['bank-soal']))
        ->toBe([KategoriReset::HasilUjian, KategoriReset::Ujian, KategoriReset::BankSoal])
        ->and(KategoriReset::lengkapi(['peserta', 'bukan-kategori']))
        ->toBe([KategoriReset::HasilUjian, KategoriReset::Peserta])
        ->and(KategoriReset::lengkapi(['master-data']))
        ->toBe(KategoriReset::cases());
});

test('resetting ujian removes ujian and hasil ujian but keeps peserta and bank soal', function () {
    isiDataLengkap();

    app(ResetDataService::class)->jalankan(['ujian']);

    expect(Ujian::count())->toBe(0)
        ->and(PesertaUjian::count())->toBe(0)
        ->and(PesertaSoal::count())->toBe(0)
        ->and(Peserta::count())->toBe(1)
        ->and(Soal::count())->toBe(1)
        ->and(BankSoal::count())->toBe(1);
});

test('resetting peserta keeps ujian and bank soal', function () {
    isiDataLengkap();

    app(ResetDataService::class)->jalankan(['peserta']);

    expect(Peserta::count())->toBe(0)
        ->and(PesertaUjian::count())->toBe(0)
        ->and(Ujian::count())->toBe(1)
        ->and(BankSoal::count())->toBe(1);
});

test('resetting master data wipes everything except admin accounts', function () {
    isiDataLengkap();
    $admin = User::factory()->create();

    $hasil = app(ResetDataService::class)->jalankan(['master-data'], $admin);

    expect(Jenjang::count() + Pelajaran::count() + BankSoal::count() + Soal::count() + Ujian::count() + Peserta::count() + PesertaUjian::count())->toBe(0)
        ->and(User::whereKey($admin->id)->exists())->toBeTrue()
        ->and($hasil['terhapus'])->toMatchArray(['jenjang' => 1, 'soal' => 1, 'peserta_soal' => 1]);
});

test('it removes category files but keeps backups', function () {
    isiDataLengkap();
    Storage::disk('public')->put('soal/1/gambar.jpeg', 'x');
    Storage::disk('local')->put('imports/soal/1/soal.xlsx', 'x');
    Storage::disk('local')->put('imports/peserta/peserta.xlsx', 'x');

    $hasil = app(ResetDataService::class)->jalankan(['bank-soal']);

    Storage::disk('public')->assertMissing('soal/1/gambar.jpeg');
    Storage::disk('local')->assertMissing('imports/soal/1/soal.xlsx');
    // Bank Soal doesn't pull in Peserta, so its import archive stays.
    Storage::disk('local')->assertExists('imports/peserta/peserta.xlsx');
    Storage::disk('local')->assertExists($hasil['backup']);
});

test('the backup has one sheet per deleted table and never contains peserta passwords', function () {
    Excel::fake();
    $this->freezeTime();
    isiDataLengkap();

    app(ResetDataService::class)->jalankan(['peserta']);

    Excel::assertStored('backups/reset-'.now()->format('Ymd_His').'.xlsx', 'local', function (BackupSebelumResetExport $export) {
        $sheets = collect($export->sheets())->keyBy(fn ($sheet) => $sheet->title());

        return $sheets->keys()->all() === ['peserta', 'peserta_pelajaran', 'peserta_ujian', 'peserta_soal']
            && array_intersect($sheets['peserta']->headings(), ['password', 'password_plain', 'remember_token']) === []
            && in_array('noreg', $sheets['peserta']->headings(), true);
    });
});

test('it refuses while a peserta is mid-attempt, deleting nothing', function () {
    $pesertaSoal = isiDataLengkap();
    $pesertaSoal->pesertaUjian->ujian->update(['sesi_mulai' => now()->subHour(), 'sesi_selesai' => now()->addHour()]);

    expect(fn () => app(ResetDataService::class)->jalankan(['peserta']))->toThrow(ValidationException::class);

    expect(Peserta::count())->toBe(1)
        ->and(Storage::disk('local')->files('backups'))->toBeEmpty();
});

test('ids restart at 1 once a table is emptied', function () {
    isiDataLengkap();
    Jenjang::factory()->count(3)->create();

    app(ResetDataService::class)->jalankan(['master-data']);

    expect(Jenjang::factory()->create()->id)->toBe(1);
});
