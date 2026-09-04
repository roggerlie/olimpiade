<?php

use App\Models\BankSoal;
use App\Models\SiswaUjian;
use App\Models\Soal;
use App\Models\Ujian;
use App\Services\ExamAttemptService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it draws exactly jumlah_soal questions from the bank, in random order', function () {
    $bankSoal = BankSoal::factory()->create();
    $soal = Soal::factory()->count(10)->create(['bank_soal_id' => $bankSoal->id]);
    $siswaUjian = SiswaUjian::factory()->create([
        'ujian_id' => Ujian::factory()->create(['bank_soal_id' => $bankSoal->id, 'jumlah_soal' => 4]),
    ]);

    (new ExamAttemptService)->mulai($siswaUjian);

    $siswaSoal = $siswaUjian->siswaSoal()->orderBy('urutan')->get();
    expect($siswaSoal)->toHaveCount(4)
        ->and($siswaSoal->pluck('soal_id')->unique())->toHaveCount(4)
        ->and($siswaSoal->pluck('soal_id')->diff($soal->pluck('id')))->toBeEmpty()
        ->and($siswaSoal->pluck('urutan')->all())->toBe([1, 2, 3, 4]);
});

test('it stamps waktu_mulai', function () {
    $bankSoal = BankSoal::factory()->create();
    Soal::factory()->count(3)->create(['bank_soal_id' => $bankSoal->id]);
    $siswaUjian = SiswaUjian::factory()->create([
        'ujian_id' => Ujian::factory()->create(['bank_soal_id' => $bankSoal->id, 'jumlah_soal' => 2]),
    ]);

    expect($siswaUjian->waktu_mulai)->toBeNull();

    (new ExamAttemptService)->mulai($siswaUjian);

    expect($siswaUjian->fresh()->waktu_mulai)->not->toBeNull();
});

test('it is idempotent — a second call never re-shuffles or resets an already-started attempt', function () {
    $bankSoal = BankSoal::factory()->create();
    Soal::factory()->count(10)->create(['bank_soal_id' => $bankSoal->id]);
    $siswaUjian = SiswaUjian::factory()->create([
        'ujian_id' => Ujian::factory()->create(['bank_soal_id' => $bankSoal->id, 'jumlah_soal' => 4]),
    ]);

    $service = new ExamAttemptService;
    $service->mulai($siswaUjian);

    $firstWaktuMulai = $siswaUjian->fresh()->waktu_mulai;
    $firstSoalIds = $siswaUjian->siswaSoal()->orderBy('urutan')->pluck('soal_id');

    $service->mulai($siswaUjian);

    expect($siswaUjian->fresh()->waktu_mulai->equalTo($firstWaktuMulai))->toBeTrue()
        ->and($siswaUjian->siswaSoal()->count())->toBe(4)
        ->and($siswaUjian->siswaSoal()->orderBy('urutan')->pluck('soal_id')->all())->toBe($firstSoalIds->all());
});
