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

test('each peserta gets their own shuffled order of the soal', function () {
    $bankSoal = BankSoal::factory()->create();
    Soal::factory()->count(20)->create(['bank_soal_id' => $bankSoal->id]);
    $ujian = Ujian::factory()->create(['bank_soal_id' => $bankSoal->id, 'jumlah_soal' => 20]);
    $pertama = PesertaUjian::factory()->create(['ujian_id' => $ujian->id]);
    $kedua = PesertaUjian::factory()->create(['ujian_id' => $ujian->id]);

    $service = new ExamAttemptService;
    $service->mulai($pertama);
    $service->mulai($kedua);

    $urutan = fn (PesertaUjian $p) => $p->pesertaSoal()->orderBy('urutan')->pluck('soal_id')->all();

    // Same 20 soal for both (jumlah_soal = the whole bank), so only the
    // order can differ — and two identical shuffles of 20 are a 1-in-20!
    // (~2.4e18) chance, i.e. this never flakes. Without inRandomOrder()
    // both would come back in the same (id) order and this fails.
    expect($urutan($pertama))->not->toBe($urutan($kedua))
        ->and(collect($urutan($pertama))->sort()->values()->all())->toBe(collect($urutan($kedua))->sort()->values()->all());
});

test('when the bank holds more soal than jumlah_soal, peserta draw different subsets', function () {
    $bankSoal = BankSoal::factory()->create();
    Soal::factory()->count(30)->create(['bank_soal_id' => $bankSoal->id]);
    $ujian = Ujian::factory()->create(['bank_soal_id' => $bankSoal->id, 'jumlah_soal' => 10]);
    $peserta = PesertaUjian::factory()->count(5)->create(['ujian_id' => $ujian->id]);

    $service = new ExamAttemptService;
    $peserta->each(fn (PesertaUjian $p) => $service->mulai($p));

    // Five draws of 10 out of 30 all landing on the exact same set is
    // (1 / C(30,10))^4 — effectively impossible unless the draw isn't random.
    $himpunan = $peserta->map(fn (PesertaUjian $p) => $p->pesertaSoal()->pluck('soal_id')->sort()->values()->all());

    expect($himpunan->unique(fn ($ids) => implode(',', $ids)))->not->toHaveCount(1);
});

test('a second "Mulai" racing the first (stale model) neither errors nor double-draws', function () {
    $bankSoal = BankSoal::factory()->create();
    Soal::factory()->count(10)->create(['bank_soal_id' => $bankSoal->id]);
    $pesertaUjian = PesertaUjian::factory()->create([
        'ujian_id' => Ujian::factory()->create(['bank_soal_id' => $bankSoal->id, 'jumlah_soal' => 4]),
    ]);

    // Two requests (double click / two tabs) each loaded the attempt
    // before either had started it — both see waktu_mulai = null.
    $requestA = PesertaUjian::find($pesertaUjian->id);
    $requestB = PesertaUjian::find($pesertaUjian->id);

    $service = new ExamAttemptService;
    $service->mulai($requestA);
    $soalSetelahA = $pesertaUjian->pesertaSoal()->orderBy('urutan')->pluck('soal_id')->all();

    $service->mulai($requestB);

    expect($pesertaUjian->pesertaSoal()->count())->toBe(4)
        ->and($pesertaUjian->pesertaSoal()->orderBy('urutan')->pluck('soal_id')->all())->toBe($soalSetelahA)
        ->and($requestB->waktu_mulai)->not->toBeNull();
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
