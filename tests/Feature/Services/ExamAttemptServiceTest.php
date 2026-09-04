<?php

use App\Models\BankSoal;
use App\Models\PesertaUjian;
use App\Models\Soal;
use App\Models\Ujian;
use App\Services\ExamAttemptService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it draws exactly jumlah_soal questions from the bank, in random order', function () {
    $bankSoal = BankSoal::factory()->create();
    $soal = Soal::factory()->count(10)->create(['bank_soal_id' => $bankSoal->id]);
    $pesertaUjian = PesertaUjian::factory()->create([
        'ujian_id' => Ujian::factory()->create(['bank_soal_id' => $bankSoal->id, 'jumlah_soal' => 4]),
    ]);

    (new ExamAttemptService)->mulai($pesertaUjian);

    $pesertaSoal = $pesertaUjian->pesertaSoal()->orderBy('urutan')->get();
    expect($pesertaSoal)->toHaveCount(4)
        ->and($pesertaSoal->pluck('soal_id')->unique())->toHaveCount(4)
        ->and($pesertaSoal->pluck('soal_id')->diff($soal->pluck('id')))->toBeEmpty()
        ->and($pesertaSoal->pluck('urutan')->all())->toBe([1, 2, 3, 4]);
});

test('it stamps waktu_mulai', function () {
    $bankSoal = BankSoal::factory()->create();
    Soal::factory()->count(3)->create(['bank_soal_id' => $bankSoal->id]);
    $pesertaUjian = PesertaUjian::factory()->create([
        'ujian_id' => Ujian::factory()->create(['bank_soal_id' => $bankSoal->id, 'jumlah_soal' => 2]),
    ]);

    expect($pesertaUjian->waktu_mulai)->toBeNull();

    (new ExamAttemptService)->mulai($pesertaUjian);

    expect($pesertaUjian->fresh()->waktu_mulai)->not->toBeNull();
});

test('it is idempotent — a second call never re-shuffles or resets an already-started attempt', function () {
    $bankSoal = BankSoal::factory()->create();
    Soal::factory()->count(10)->create(['bank_soal_id' => $bankSoal->id]);
    $pesertaUjian = PesertaUjian::factory()->create([
        'ujian_id' => Ujian::factory()->create(['bank_soal_id' => $bankSoal->id, 'jumlah_soal' => 4]),
    ]);

    $service = new ExamAttemptService;
    $service->mulai($pesertaUjian);

    $firstWaktuMulai = $pesertaUjian->fresh()->waktu_mulai;
    $firstSoalIds = $pesertaUjian->pesertaSoal()->orderBy('urutan')->pluck('soal_id');

    $service->mulai($pesertaUjian);

    expect($pesertaUjian->fresh()->waktu_mulai->equalTo($firstWaktuMulai))->toBeTrue()
        ->and($pesertaUjian->pesertaSoal()->count())->toBe(4)
        ->and($pesertaUjian->pesertaSoal()->orderBy('urutan')->pluck('soal_id')->all())->toBe($firstSoalIds->all());
});
