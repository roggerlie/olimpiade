<?php

use App\Models\BankSoal;
use App\Models\PesertaUjian;
use App\Models\Soal;
use App\Models\Ujian;
use App\Services\ExamAttemptService;
use App\Support\LogAktivitas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

function logUjian(string $event): Activity
{
    return Activity::query()->where('log_name', LogAktivitas::UJIAN)->where('event', $event)->sole();
}

test('starting an exam is logged once, even when Mulai is clicked again', function () {
    $peserta = actingAsPeserta();
    $bankSoal = BankSoal::factory()->create();
    Soal::factory()->count(3)->create(['bank_soal_id' => $bankSoal->id]);
    $ujian = Ujian::factory()->create([
        'nama' => 'OSN IPA SD',
        'bank_soal_id' => $bankSoal->id,
        'jumlah_soal' => 3,
        'sesi_mulai' => now()->subMinute(),
        'sesi_selesai' => now()->addHour(),
    ]);
    $pesertaUjian = PesertaUjian::factory()->create(['peserta_id' => $peserta->id, 'ujian_id' => $ujian->id]);

    $this->post(route('cbt.ujian.mulai', $pesertaUjian))->assertRedirect(route('cbt.ujian.kerjakan', $pesertaUjian));
    $this->post(route('cbt.ujian.mulai', $pesertaUjian));

    $log = logUjian('mulai_ujian');

    expect($log->causer->is($peserta))->toBeTrue()
        ->and($log->subject->is($pesertaUjian))->toBeTrue()
        ->and($log->properties['ujian'])->toBe('OSN IPA SD');
});

test('a student submitting is logged as a manual finish with the score', function () {
    $peserta = actingAsPeserta();
    $pesertaUjian = PesertaUjian::factory()->create(['peserta_id' => $peserta->id, 'waktu_mulai' => now()]);

    $this->postJson(route('cbt.ujian.submit', $pesertaUjian))->assertOk();

    $log = logUjian('submit_ujian');

    expect($log->causer->is($peserta))->toBeTrue()
        ->and($log->properties['otomatis'])->toBeFalse()
        ->and($log->properties['nilai'])->toEqual(0);
});

test('a direct call to an already-started attempt logs nothing new', function () {
    $pesertaUjian = PesertaUjian::factory()->create(['waktu_mulai' => now()]);

    (new ExamAttemptService)->mulai($pesertaUjian);

    expect(Activity::query()->where('log_name', LogAktivitas::UJIAN)->exists())->toBeFalse();
});
