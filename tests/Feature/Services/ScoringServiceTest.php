<?php

use App\Models\PesertaSoal;
use App\Models\PesertaUjian;
use App\Models\Soal;
use App\Services\ScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it tallies benar/salah against the real jawaban and computes nilai as benar*9 - salah', function () {
    $pesertaUjian = PesertaUjian::factory()->create();

    $benar1 = Soal::factory()->create(['jawaban' => 'A']);
    $benar2 = Soal::factory()->create(['jawaban' => 'B']);
    $salah = Soal::factory()->create(['jawaban' => 'C']);
    $tidakDijawab = Soal::factory()->create(['jawaban' => 'D']);

    PesertaSoal::factory()->create(['peserta_ujian_id' => $pesertaUjian->id, 'soal_id' => $benar1->id, 'urutan' => 1, 'jawaban' => 'A']);
    PesertaSoal::factory()->create(['peserta_ujian_id' => $pesertaUjian->id, 'soal_id' => $benar2->id, 'urutan' => 2, 'jawaban' => 'B']);
    PesertaSoal::factory()->create(['peserta_ujian_id' => $pesertaUjian->id, 'soal_id' => $salah->id, 'urutan' => 3, 'jawaban' => 'A']);
    PesertaSoal::factory()->create(['peserta_ujian_id' => $pesertaUjian->id, 'soal_id' => $tidakDijawab->id, 'urutan' => 4, 'jawaban' => null]);

    (new ScoringService)->submit($pesertaUjian);

    $pesertaUjian->refresh();
    expect($pesertaUjian->benar)->toBe(2)
        ->and($pesertaUjian->salah)->toBe(1)
        ->and((float) $pesertaUjian->nilai)->toBe(17.0) // 2*9 - 1
        ->and($pesertaUjian->waktu_selesai)->not->toBeNull();
});

test('it allows a negative nilai, matching the original scoring rule (no floor at zero)', function () {
    $pesertaUjian = PesertaUjian::factory()->create();

    for ($i = 0; $i < 5; $i++) {
        $soal = Soal::factory()->create(['jawaban' => 'A']);
        PesertaSoal::factory()->create(['peserta_ujian_id' => $pesertaUjian->id, 'soal_id' => $soal->id, 'urutan' => $i + 1, 'jawaban' => 'B']);
    }

    (new ScoringService)->submit($pesertaUjian);

    expect((float) $pesertaUjian->fresh()->nilai)->toBe(-5.0); // 0*9 - 5
});

test('it is idempotent — submitting an already-submitted attempt changes nothing', function () {
    $pesertaUjian = PesertaUjian::factory()->selesai()->create(['benar' => 5, 'salah' => 1, 'nilai' => 44]);
    $waktuSelesaiAsli = $pesertaUjian->waktu_selesai;

    (new ScoringService)->submit($pesertaUjian);

    $pesertaUjian->refresh();
    expect((float) $pesertaUjian->nilai)->toBe(44.0)
        ->and($pesertaUjian->waktu_selesai->equalTo($waktuSelesaiAsli))->toBeTrue();
});
