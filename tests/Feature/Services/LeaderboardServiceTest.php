<?php

use App\Models\PesertaUjian;
use App\Models\Ujian;
use App\Services\LeaderboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it ranks by nilai descending', function () {
    $ujian = Ujian::factory()->create();
    $rendah = PesertaUjian::factory()->selesai()->create(['ujian_id' => $ujian->id, 'nilai' => 18]);
    $tinggi = PesertaUjian::factory()->selesai()->create(['ujian_id' => $ujian->id, 'nilai' => 81]);
    $sedang = PesertaUjian::factory()->selesai()->create(['ujian_id' => $ujian->id, 'nilai' => 45]);

    $ranking = (new LeaderboardService)->ranking($ujian);

    expect($ranking->pluck('id')->all())->toBe([$tinggi->id, $sedang->id, $rendah->id]);
});

test('it breaks a nilai tie by the fastest durasi pengerjaan', function () {
    $ujian = Ujian::factory()->create();
    $lambat = PesertaUjian::factory()->create([
        'ujian_id' => $ujian->id, 'nilai' => 50,
        'waktu_mulai' => now()->subMinutes(60), 'waktu_selesai' => now(),
    ]);
    $cepat = PesertaUjian::factory()->create([
        'ujian_id' => $ujian->id, 'nilai' => 50,
        'waktu_mulai' => now()->subMinutes(20), 'waktu_selesai' => now(),
    ]);

    $ranking = (new LeaderboardService)->ranking($ujian);

    expect($ranking->pluck('id')->all())->toBe([$cepat->id, $lambat->id]);
});

test('it excludes attempts that were never submitted', function () {
    $ujian = Ujian::factory()->create();
    $selesai = PesertaUjian::factory()->selesai()->create(['ujian_id' => $ujian->id]);
    PesertaUjian::factory()->create(['ujian_id' => $ujian->id, 'waktu_mulai' => now()]); // in progress
    PesertaUjian::factory()->create(['ujian_id' => $ujian->id]); // not started

    $ranking = (new LeaderboardService)->ranking($ujian);

    expect($ranking->pluck('id')->all())->toBe([$selesai->id]);
});

test('it only ranks attempts for the given ujian', function () {
    $ujian = Ujian::factory()->create();
    $lain = Ujian::factory()->create();
    $milikSaya = PesertaUjian::factory()->selesai()->create(['ujian_id' => $ujian->id]);
    PesertaUjian::factory()->selesai()->create(['ujian_id' => $lain->id]);

    $ranking = (new LeaderboardService)->ranking($ujian);

    expect($ranking->pluck('id')->all())->toBe([$milikSaya->id]);
});
