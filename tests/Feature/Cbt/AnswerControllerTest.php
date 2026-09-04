<?php

use App\Models\PesertaSoal;
use App\Models\PesertaUjian;
use App\Models\Soal;
use App\Models\Ujian;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('simpan saves the chosen jawaban for one soal', function () {
    $peserta = actingAsPeserta();
    $ujian = Ujian::factory()->create(['sesi_mulai' => now()->subMinute(), 'sesi_selesai' => now()->addHour()]);
    $pesertaUjian = PesertaUjian::factory()->create(['peserta_id' => $peserta->id, 'ujian_id' => $ujian->id, 'waktu_mulai' => now()]);
    $soal = Soal::factory()->create();
    $pesertaSoal = PesertaSoal::factory()->create(['peserta_ujian_id' => $pesertaUjian->id, 'soal_id' => $soal->id, 'urutan' => 1]);

    $this->postJson(route('cbt.ujian.jawab', $pesertaUjian), ['soal_id' => $soal->id, 'jawaban' => 'C'])
        ->assertOk()
        ->assertJson(['tersimpan' => true]);

    expect($pesertaSoal->fresh()->jawaban)->toBe('C');
});

test('simpan rejects an invalid jawaban letter', function () {
    $peserta = actingAsPeserta();
    $pesertaUjian = PesertaUjian::factory()->create(['peserta_id' => $peserta->id, 'waktu_mulai' => now()]);

    $this->postJson(route('cbt.ujian.jawab', $pesertaUjian), ['soal_id' => 1, 'jawaban' => 'Z'])
        ->assertStatus(422);
});

test('simpan is rejected once the attempt is already submitted', function () {
    $peserta = actingAsPeserta();
    $pesertaUjian = PesertaUjian::factory()->selesai()->create(['peserta_id' => $peserta->id]);

    $this->postJson(route('cbt.ujian.jawab', $pesertaUjian), ['soal_id' => 1, 'jawaban' => 'A'])
        ->assertStatus(422)
        ->assertJson(['message' => 'Ujian sudah dikumpulkan.']);
});

test('simpan is rejected once the deadline has passed', function () {
    $peserta = actingAsPeserta();
    $ujian = Ujian::factory()->create(['durasi_detik' => 60, 'sesi_selesai' => now()->addHour()]);
    $pesertaUjian = PesertaUjian::factory()->create([
        'peserta_id' => $peserta->id,
        'ujian_id' => $ujian->id,
        'waktu_mulai' => now()->subMinutes(5),
    ]);

    $this->postJson(route('cbt.ujian.jawab', $pesertaUjian), ['soal_id' => 1, 'jawaban' => 'A'])
        ->assertStatus(422)
        ->assertJson(['message' => 'Waktu ujian sudah habis.']);
});

test('simpan is forbidden for another peserta attempt', function () {
    actingAsPeserta();
    $pesertaUjianOrangLain = PesertaUjian::factory()->create(['waktu_mulai' => now()]);

    $this->postJson(route('cbt.ujian.jawab', $pesertaUjianOrangLain), ['soal_id' => 1, 'jawaban' => 'A'])
        ->assertForbidden();
});

test('submit scores the attempt and returns the hasil redirect url', function () {
    $peserta = actingAsPeserta();
    $pesertaUjian = PesertaUjian::factory()->create(['peserta_id' => $peserta->id, 'waktu_mulai' => now()]);
    $benar = Soal::factory()->create(['jawaban' => 'A']);
    PesertaSoal::factory()->create(['peserta_ujian_id' => $pesertaUjian->id, 'soal_id' => $benar->id, 'urutan' => 1, 'jawaban' => 'A']);

    $this->postJson(route('cbt.ujian.submit', $pesertaUjian))
        ->assertOk()
        ->assertJson(['redirect' => route('cbt.ujian.hasil', $pesertaUjian)]);

    $pesertaUjian->refresh();
    expect($pesertaUjian->benar)->toBe(1)
        ->and((float) $pesertaUjian->nilai)->toBe(9.0);
});

test('submit is idempotent', function () {
    $peserta = actingAsPeserta();
    $pesertaUjian = PesertaUjian::factory()->selesai()->create(['peserta_id' => $peserta->id, 'nilai' => 44]);

    $this->postJson(route('cbt.ujian.submit', $pesertaUjian))->assertOk();

    expect((float) $pesertaUjian->fresh()->nilai)->toBe(44.0);
});

test('submit is forbidden for another peserta attempt', function () {
    actingAsPeserta();
    $pesertaUjianOrangLain = PesertaUjian::factory()->create(['waktu_mulai' => now()]);

    $this->postJson(route('cbt.ujian.submit', $pesertaUjianOrangLain))->assertForbidden();
});
