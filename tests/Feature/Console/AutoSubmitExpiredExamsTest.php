<?php

use App\Models\PesertaSoal;
use App\Models\PesertaUjian;
use App\Models\Soal;
use App\Models\Ujian;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it submits an attempt whose deadline has passed but was never submitted', function () {
    $ujian = Ujian::factory()->create(['durasi_detik' => 60, 'sesi_selesai' => now()->addHour()]);
    $pesertaUjian = PesertaUjian::factory()->create(['ujian_id' => $ujian->id, 'waktu_mulai' => now()->subMinutes(5)]);
    $benar = Soal::factory()->create(['jawaban' => 'A']);
    PesertaSoal::factory()->create(['peserta_ujian_id' => $pesertaUjian->id, 'soal_id' => $benar->id, 'urutan' => 1, 'jawaban' => 'A']);

    $this->artisan('exam:auto-submit')->assertSuccessful();

    $pesertaUjian->refresh();
    expect($pesertaUjian->waktu_selesai)->not->toBeNull()
        ->and($pesertaUjian->benar)->toBe(1)
        ->and((float) $pesertaUjian->nilai)->toBe(9.0);
});

test('it leaves an attempt alone whose deadline has not passed yet', function () {
    $ujian = Ujian::factory()->create(['durasi_detik' => 3600, 'sesi_selesai' => now()->addHours(2)]);
    $pesertaUjian = PesertaUjian::factory()->create(['ujian_id' => $ujian->id, 'waktu_mulai' => now()->subMinutes(5)]);

    $this->artisan('exam:auto-submit');

    expect($pesertaUjian->fresh()->waktu_selesai)->toBeNull();
});

test('it ignores attempts that have not started yet', function () {
    $pesertaUjian = PesertaUjian::factory()->create(['waktu_mulai' => null]);

    $this->artisan('exam:auto-submit');

    expect($pesertaUjian->fresh()->waktu_selesai)->toBeNull();
});

test('it ignores attempts that are already submitted', function () {
    $pesertaUjian = PesertaUjian::factory()->selesai()->create(['nilai' => 44]);
    $waktuSelesaiAsli = $pesertaUjian->waktu_selesai;

    $this->artisan('exam:auto-submit');

    expect($pesertaUjian->fresh()->waktu_selesai->equalTo($waktuSelesaiAsli))->toBeTrue()
        ->and((float) $pesertaUjian->fresh()->nilai)->toBe(44.0);
});
