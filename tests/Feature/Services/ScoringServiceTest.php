<?php

use App\Models\SiswaSoal;
use App\Models\SiswaUjian;
use App\Models\Soal;
use App\Services\ScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it tallies benar/salah against the real jawaban and computes nilai as benar*9 - salah', function () {
    $siswaUjian = SiswaUjian::factory()->create();

    $benar1 = Soal::factory()->create(['jawaban' => 'A']);
    $benar2 = Soal::factory()->create(['jawaban' => 'B']);
    $salah = Soal::factory()->create(['jawaban' => 'C']);
    $tidakDijawab = Soal::factory()->create(['jawaban' => 'D']);

    SiswaSoal::factory()->create(['siswa_ujian_id' => $siswaUjian->id, 'soal_id' => $benar1->id, 'urutan' => 1, 'jawaban' => 'A']);
    SiswaSoal::factory()->create(['siswa_ujian_id' => $siswaUjian->id, 'soal_id' => $benar2->id, 'urutan' => 2, 'jawaban' => 'B']);
    SiswaSoal::factory()->create(['siswa_ujian_id' => $siswaUjian->id, 'soal_id' => $salah->id, 'urutan' => 3, 'jawaban' => 'A']);
    SiswaSoal::factory()->create(['siswa_ujian_id' => $siswaUjian->id, 'soal_id' => $tidakDijawab->id, 'urutan' => 4, 'jawaban' => null]);

    (new ScoringService)->submit($siswaUjian);

    $siswaUjian->refresh();
    expect($siswaUjian->benar)->toBe(2)
        ->and($siswaUjian->salah)->toBe(1)
        ->and((float) $siswaUjian->nilai)->toBe(17.0) // 2*9 - 1
        ->and($siswaUjian->waktu_selesai)->not->toBeNull();
});

test('it allows a negative nilai, matching the original scoring rule (no floor at zero)', function () {
    $siswaUjian = SiswaUjian::factory()->create();

    for ($i = 0; $i < 5; $i++) {
        $soal = Soal::factory()->create(['jawaban' => 'A']);
        SiswaSoal::factory()->create(['siswa_ujian_id' => $siswaUjian->id, 'soal_id' => $soal->id, 'urutan' => $i + 1, 'jawaban' => 'B']);
    }

    (new ScoringService)->submit($siswaUjian);

    expect((float) $siswaUjian->fresh()->nilai)->toBe(-5.0); // 0*9 - 5
});

test('it is idempotent — submitting an already-submitted attempt changes nothing', function () {
    $siswaUjian = SiswaUjian::factory()->selesai()->create(['benar' => 5, 'salah' => 1, 'nilai' => 44]);
    $waktuSelesaiAsli = $siswaUjian->waktu_selesai;

    (new ScoringService)->submit($siswaUjian);

    $siswaUjian->refresh();
    expect((float) $siswaUjian->nilai)->toBe(44.0)
        ->and($siswaUjian->waktu_selesai->equalTo($waktuSelesaiAsli))->toBeTrue();
});
