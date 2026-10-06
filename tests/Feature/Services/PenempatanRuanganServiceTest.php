<?php

use App\Models\Peserta;
use App\Models\PesertaUjian;
use App\Models\Ruangan;
use App\Models\Ujian;
use App\Services\PenempatanRuanganService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function ujianJam(int $mulai, int $selesai, string $nama = 'Ujian'): Ujian
{
    $hari = now()->addDay()->startOfDay();

    return Ujian::factory()->create([
        'nama' => $nama,
        'sesi_mulai' => $hari->copy()->setHour($mulai),
        'sesi_selesai' => $hari->copy()->setHour($selesai),
    ]);
}

/**
 * @param  array<string, mixed>  $peserta
 */
function daftarkan(Ujian $ujian, ?Ruangan $ruangan = null, array $peserta = []): PesertaUjian
{
    return PesertaUjian::factory()->create([
        'ujian_id' => $ujian->id,
        'ruangan_id' => $ruangan?->id,
        'peserta_id' => Peserta::factory()->create($peserta)->id,
    ]);
}

test('occupancy counts peserta of other ujian in the same room only when their times overlap', function () {
    $ruangan = Ruangan::factory()->create(['kapasitas' => 3]);
    $ujian = ujianJam(8, 10);
    $bentrok = ujianJam(9, 11, 'Ujian IPA');
    $tidakBentrok = ujianJam(10, 12);

    daftarkan($ujian, $ruangan);
    daftarkan($ujian, $ruangan);
    daftarkan($bentrok, $ruangan);
    daftarkan($bentrok, $ruangan);
    daftarkan($tidakBentrok, $ruangan);

    $pemakaian = app(PenempatanRuanganService::class)->pemakaian($ujian)->firstWhere('ruangan.id', $ruangan->id);

    expect($pemakaian)
        ->terisi->toBe(2)
        ->terisiUjianLain->toBe(2)
        ->ujianLain->toBe(['Ujian IPA'])
        ->total->toBe(4)
        ->sisa->toBe(0)
        ->melebihi->toBeTrue();
});

test('bagi otomatis fills the chosen rooms in name order up to their remaining kapasitas, never overfilling', function () {
    $ujian = ujianJam(8, 10);
    $labA = Ruangan::factory()->create(['nama' => 'Lab A', 'kapasitas' => 2]);
    $labB = Ruangan::factory()->create(['nama' => 'Lab B', 'kapasitas' => 2]);
    $tidakDipilih = Ruangan::factory()->create(['nama' => 'Lab C', 'kapasitas' => 50]);
    $sudahDitempatkan = daftarkan($ujian, $labB);
    // A different ujian at the same time already uses one of Lab A's seats.
    daftarkan(ujianJam(9, 11), $labA);

    $belum = collect(range(1, 4))->map(fn (int $i) => daftarkan($ujian, peserta: ['noreg' => "100000000{$i}"]));

    $hasil = app(PenempatanRuanganService::class)
        ->bagiOtomatis($ujian, [$labA->id, $labB->id], PenempatanRuanganService::URUTAN_NOREG);

    expect($hasil)->toBe(['ditempatkan' => 2, 'tidakKebagian' => 2])
        ->and($belum->map(fn (PesertaUjian $pu) => $pu->fresh()->ruangan_id)->all())
        ->toBe([$labA->id, $labB->id, null, null])
        ->and($sudahDitempatkan->fresh()->ruangan_id)->toBe($labB->id)
        ->and(PesertaUjian::where('ruangan_id', $tidakDipilih->id)->exists())->toBeFalse();
});

test('campur asal sekolah alternates schools, unlike urut no. registrasi', function (string $urutan, array $sekolahDiLabA) {
    $ujian = ujianJam(8, 10);
    $labA = Ruangan::factory()->create(['nama' => 'Lab A', 'kapasitas' => 2]);
    Ruangan::factory()->create(['nama' => 'Lab B', 'kapasitas' => 10]);

    daftarkan($ujian, peserta: ['noreg' => '1000000001', 'nama' => 'Ani', 'asal_sekolah' => 'SMA 1']);
    daftarkan($ujian, peserta: ['noreg' => '1000000002', 'nama' => 'Budi', 'asal_sekolah' => 'SMA 1']);
    daftarkan($ujian, peserta: ['noreg' => '1000000003', 'nama' => 'Caca', 'asal_sekolah' => 'SMA 1']);
    daftarkan($ujian, peserta: ['noreg' => '1000000004', 'nama' => 'Dodi', 'asal_sekolah' => 'SMA 2']);

    app(PenempatanRuanganService::class)->bagiOtomatis($ujian, Ruangan::pluck('id')->all(), $urutan);

    $sekolah = PesertaUjian::query()->where('ruangan_id', $labA->id)->with('peserta')->get()
        ->pluck('peserta.asal_sekolah')->sort()->values()->all();

    expect($sekolah)->toBe($sekolahDiLabA);
})->with([
    'campur asal sekolah' => [PenempatanRuanganService::URUTAN_CAMPUR_SEKOLAH, ['SMA 1', 'SMA 2']],
    'urut no. registrasi' => [PenempatanRuanganService::URUTAN_NOREG, ['SMA 1', 'SMA 1']],
]);
