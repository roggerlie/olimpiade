<?php

use App\Models\SiswaSoal;
use App\Models\SiswaUjian;
use App\Models\Soal;
use App\Models\Ujian;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it submits an attempt whose deadline has passed but was never submitted', function () {
    $ujian = Ujian::factory()->create(['durasi_detik' => 60, 'sesi_selesai' => now()->addHour()]);
    $siswaUjian = SiswaUjian::factory()->create(['ujian_id' => $ujian->id, 'waktu_mulai' => now()->subMinutes(5)]);
    $benar = Soal::factory()->create(['jawaban' => 'A']);
    SiswaSoal::factory()->create(['siswa_ujian_id' => $siswaUjian->id, 'soal_id' => $benar->id, 'urutan' => 1, 'jawaban' => 'A']);

    $this->artisan('exam:auto-submit')->assertSuccessful();

    $siswaUjian->refresh();
    expect($siswaUjian->waktu_selesai)->not->toBeNull()
        ->and($siswaUjian->benar)->toBe(1)
        ->and((float) $siswaUjian->nilai)->toBe(9.0);
});

test('it leaves an attempt alone whose deadline has not passed yet', function () {
    $ujian = Ujian::factory()->create(['durasi_detik' => 3600, 'sesi_selesai' => now()->addHours(2)]);
    $siswaUjian = SiswaUjian::factory()->create(['ujian_id' => $ujian->id, 'waktu_mulai' => now()->subMinutes(5)]);

    $this->artisan('exam:auto-submit');

    expect($siswaUjian->fresh()->waktu_selesai)->toBeNull();
});

test('it ignores attempts that have not started yet', function () {
    $siswaUjian = SiswaUjian::factory()->create(['waktu_mulai' => null]);

    $this->artisan('exam:auto-submit');

    expect($siswaUjian->fresh()->waktu_selesai)->toBeNull();
});

test('it ignores attempts that are already submitted', function () {
    $siswaUjian = SiswaUjian::factory()->selesai()->create(['nilai' => 44]);
    $waktuSelesaiAsli = $siswaUjian->waktu_selesai;

    $this->artisan('exam:auto-submit');

    expect($siswaUjian->fresh()->waktu_selesai->equalTo($waktuSelesaiAsli))->toBeTrue()
        ->and((float) $siswaUjian->fresh()->nilai)->toBe(44.0);
});
