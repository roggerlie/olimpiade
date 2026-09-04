<?php

use App\Models\SiswaSoal;
use App\Models\SiswaUjian;
use App\Models\Soal;
use App\Models\Ujian;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('simpan saves the chosen jawaban for one soal', function () {
    $siswa = actingAsSiswa();
    $ujian = Ujian::factory()->create(['sesi_mulai' => now()->subMinute(), 'sesi_selesai' => now()->addHour()]);
    $siswaUjian = SiswaUjian::factory()->create(['siswa_id' => $siswa->id, 'ujian_id' => $ujian->id, 'waktu_mulai' => now()]);
    $soal = Soal::factory()->create();
    $siswaSoal = SiswaSoal::factory()->create(['siswa_ujian_id' => $siswaUjian->id, 'soal_id' => $soal->id, 'urutan' => 1]);

    $this->postJson(route('cbt.ujian.jawab', $siswaUjian), ['soal_id' => $soal->id, 'jawaban' => 'C'])
        ->assertOk()
        ->assertJson(['tersimpan' => true]);

    expect($siswaSoal->fresh()->jawaban)->toBe('C');
});

test('simpan rejects an invalid jawaban letter', function () {
    $siswa = actingAsSiswa();
    $siswaUjian = SiswaUjian::factory()->create(['siswa_id' => $siswa->id, 'waktu_mulai' => now()]);

    $this->postJson(route('cbt.ujian.jawab', $siswaUjian), ['soal_id' => 1, 'jawaban' => 'Z'])
        ->assertStatus(422);
});

test('simpan is rejected once the attempt is already submitted', function () {
    $siswa = actingAsSiswa();
    $siswaUjian = SiswaUjian::factory()->selesai()->create(['siswa_id' => $siswa->id]);

    $this->postJson(route('cbt.ujian.jawab', $siswaUjian), ['soal_id' => 1, 'jawaban' => 'A'])
        ->assertStatus(422)
        ->assertJson(['message' => 'Ujian sudah dikumpulkan.']);
});

test('simpan is rejected once the deadline has passed', function () {
    $siswa = actingAsSiswa();
    $ujian = Ujian::factory()->create(['durasi_detik' => 60, 'sesi_selesai' => now()->addHour()]);
    $siswaUjian = SiswaUjian::factory()->create([
        'siswa_id' => $siswa->id,
        'ujian_id' => $ujian->id,
        'waktu_mulai' => now()->subMinutes(5),
    ]);

    $this->postJson(route('cbt.ujian.jawab', $siswaUjian), ['soal_id' => 1, 'jawaban' => 'A'])
        ->assertStatus(422)
        ->assertJson(['message' => 'Waktu ujian sudah habis.']);
});

test('simpan is forbidden for another siswa attempt', function () {
    actingAsSiswa();
    $siswaUjianOrangLain = SiswaUjian::factory()->create(['waktu_mulai' => now()]);

    $this->postJson(route('cbt.ujian.jawab', $siswaUjianOrangLain), ['soal_id' => 1, 'jawaban' => 'A'])
        ->assertForbidden();
});

test('submit scores the attempt and returns the hasil redirect url', function () {
    $siswa = actingAsSiswa();
    $siswaUjian = SiswaUjian::factory()->create(['siswa_id' => $siswa->id, 'waktu_mulai' => now()]);
    $benar = Soal::factory()->create(['jawaban' => 'A']);
    SiswaSoal::factory()->create(['siswa_ujian_id' => $siswaUjian->id, 'soal_id' => $benar->id, 'urutan' => 1, 'jawaban' => 'A']);

    $this->postJson(route('cbt.ujian.submit', $siswaUjian))
        ->assertOk()
        ->assertJson(['redirect' => route('cbt.ujian.hasil', $siswaUjian)]);

    $siswaUjian->refresh();
    expect($siswaUjian->benar)->toBe(1)
        ->and((float) $siswaUjian->nilai)->toBe(9.0);
});

test('submit is idempotent', function () {
    $siswa = actingAsSiswa();
    $siswaUjian = SiswaUjian::factory()->selesai()->create(['siswa_id' => $siswa->id, 'nilai' => 44]);

    $this->postJson(route('cbt.ujian.submit', $siswaUjian))->assertOk();

    expect((float) $siswaUjian->fresh()->nilai)->toBe(44.0);
});

test('submit is forbidden for another siswa attempt', function () {
    actingAsSiswa();
    $siswaUjianOrangLain = SiswaUjian::factory()->create(['waktu_mulai' => now()]);

    $this->postJson(route('cbt.ujian.submit', $siswaUjianOrangLain))->assertForbidden();
});
