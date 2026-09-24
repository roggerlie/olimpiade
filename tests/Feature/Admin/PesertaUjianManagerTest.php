<?php

use App\Models\Jenjang;
use App\Models\Peserta;
use App\Models\PesertaSoal;
use App\Models\PesertaUjian;
use App\Models\Ujian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function ujianForJenjang(Jenjang $jenjang): Ujian
{
    return Ujian::factory()->create(['jenjang_id' => $jenjang->id]);
}

test('it only lists peserta belonging to the ujian jenjang', function () {
    actingAsAdmin();
    $jenjangA = Jenjang::factory()->create();
    $jenjangB = Jenjang::factory()->create();
    $ujian = ujianForJenjang($jenjangA);
    Peserta::factory()->create(['jenjang_id' => $jenjangA->id, 'nama' => 'Peserta Jenjang A']);
    Peserta::factory()->create(['jenjang_id' => $jenjangB->id, 'nama' => 'Peserta Jenjang B']);

    Livewire::test('admin.peserta-ujian.manager', ['ujianId' => $ujian->id])
        ->assertSee('Peserta Jenjang A')
        ->assertDontSee('Peserta Jenjang B');
});

test('it registers a single unregistered peserta', function () {
    actingAsAdmin();
    $jenjang = Jenjang::factory()->create();
    $ujian = ujianForJenjang($jenjang);
    $peserta = Peserta::factory()->create(['jenjang_id' => $jenjang->id]);

    Livewire::test('admin.peserta-ujian.manager', ['ujianId' => $ujian->id])
        ->call('toggleDaftar', $peserta->id);

    expect(PesertaUjian::where('peserta_id', $peserta->id)->where('ujian_id', $ujian->id)->exists())->toBeTrue();
});

test('it unregisters a peserta who has not started the exam', function () {
    actingAsAdmin();
    $jenjang = Jenjang::factory()->create();
    $ujian = ujianForJenjang($jenjang);
    $peserta = Peserta::factory()->create(['jenjang_id' => $jenjang->id]);
    PesertaUjian::factory()->create(['peserta_id' => $peserta->id, 'ujian_id' => $ujian->id]);

    Livewire::test('admin.peserta-ujian.manager', ['ujianId' => $ujian->id])
        ->call('toggleDaftar', $peserta->id);

    expect(PesertaUjian::where('peserta_id', $peserta->id)->where('ujian_id', $ujian->id)->exists())->toBeFalse();
});

test('it refuses to unregister a peserta who already started the exam', function () {
    actingAsAdmin();
    $jenjang = Jenjang::factory()->create();
    $ujian = ujianForJenjang($jenjang);
    $peserta = Peserta::factory()->create(['jenjang_id' => $jenjang->id]);
    PesertaUjian::factory()->create(['peserta_id' => $peserta->id, 'ujian_id' => $ujian->id, 'waktu_mulai' => now()]);

    Livewire::test('admin.peserta-ujian.manager', ['ujianId' => $ujian->id])
        ->call('toggleDaftar', $peserta->id)
        ->assertSet('errorMessage', fn ($message) => str_contains($message, 'sudah mulai mengerjakan'));

    expect(PesertaUjian::where('peserta_id', $peserta->id)->where('ujian_id', $ujian->id)->exists())->toBeTrue();
});

test('daftarkanYangBerminat only registers peserta who declared interest in this ujian mapel', function () {
    actingAsAdmin();
    $jenjang = Jenjang::factory()->create();
    $ujian = ujianForJenjang($jenjang);

    $berminat = Peserta::factory()->create(['jenjang_id' => $jenjang->id]);
    $berminat->pelajaranLomba()->attach($ujian->pelajaran_id);

    $tidakBerminat = Peserta::factory()->create(['jenjang_id' => $jenjang->id]);

    Livewire::test('admin.peserta-ujian.manager', ['ujianId' => $ujian->id])
        ->call('daftarkanYangBerminat')
        ->assertSet('statusMessage', fn ($message) => str_contains($message, '1 peserta'));

    expect(PesertaUjian::where('peserta_id', $berminat->id)->where('ujian_id', $ujian->id)->exists())->toBeTrue()
        ->and(PesertaUjian::where('peserta_id', $tidakBerminat->id)->where('ujian_id', $ujian->id)->exists())->toBeFalse();
});

test('daftarkanYangBerminat skips a peserta already registered', function () {
    actingAsAdmin();
    $jenjang = Jenjang::factory()->create();
    $ujian = ujianForJenjang($jenjang);

    $sudahTerdaftar = Peserta::factory()->create(['jenjang_id' => $jenjang->id]);
    $sudahTerdaftar->pelajaranLomba()->attach($ujian->pelajaran_id);
    PesertaUjian::factory()->create(['peserta_id' => $sudahTerdaftar->id, 'ujian_id' => $ujian->id]);

    Livewire::test('admin.peserta-ujian.manager', ['ujianId' => $ujian->id])
        ->call('daftarkanYangBerminat')
        ->assertSet('statusMessage', fn ($message) => str_contains($message, '0 peserta'));

    expect(PesertaUjian::where('ujian_id', $ujian->id)->count())->toBe(1);
});

test('resetProgres wipes timing, score and answers but keeps the registration', function () {
    actingAsAdmin();
    $jenjang = Jenjang::factory()->create();
    $ujian = ujianForJenjang($jenjang);
    $peserta = Peserta::factory()->create(['jenjang_id' => $jenjang->id]);
    $pesertaUjian = PesertaUjian::factory()->selesai()->create(['peserta_id' => $peserta->id, 'ujian_id' => $ujian->id]);
    PesertaSoal::factory()->count(3)->create(['peserta_ujian_id' => $pesertaUjian->id]);

    Livewire::test('admin.peserta-ujian.manager', ['ujianId' => $ujian->id])
        ->call('resetProgres', $peserta->id)
        ->assertSet('statusMessage', fn ($message) => str_contains($message, 'direset'));

    $pesertaUjian->refresh();
    expect($pesertaUjian->waktu_mulai)->toBeNull()
        ->and($pesertaUjian->waktu_selesai)->toBeNull()
        ->and($pesertaUjian->benar)->toBeNull()
        ->and($pesertaUjian->salah)->toBeNull()
        ->and($pesertaUjian->nilai)->toBeNull()
        ->and(PesertaSoal::where('peserta_ujian_id', $pesertaUjian->id)->count())->toBe(0)
        // still registered — resetProgres only clears progress, not the enrolment.
        ->and(PesertaUjian::where('id', $pesertaUjian->id)->exists())->toBeTrue();
});

test('resetSemua only touches attempts that have actually started', function () {
    actingAsAdmin();
    $jenjang = Jenjang::factory()->create();
    $ujian = ujianForJenjang($jenjang);

    $sudahMulai = Peserta::factory()->create(['jenjang_id' => $jenjang->id]);
    $attemptMulai = PesertaUjian::factory()->selesai()->create(['peserta_id' => $sudahMulai->id, 'ujian_id' => $ujian->id]);
    PesertaSoal::factory()->count(2)->create(['peserta_ujian_id' => $attemptMulai->id]);

    $belumMulai = Peserta::factory()->create(['jenjang_id' => $jenjang->id]);
    PesertaUjian::factory()->create(['peserta_id' => $belumMulai->id, 'ujian_id' => $ujian->id]);

    Livewire::test('admin.peserta-ujian.manager', ['ujianId' => $ujian->id])
        ->call('resetSemua')
        ->assertSet('statusMessage', fn ($message) => str_contains($message, '1 progres'));

    expect($attemptMulai->fresh()->waktu_mulai)->toBeNull()
        ->and(PesertaSoal::where('peserta_ujian_id', $attemptMulai->id)->count())->toBe(0);

    $belumMulaiAttempt = PesertaUjian::where('peserta_id', $belumMulai->id)->first();
    expect($belumMulaiAttempt)->not->toBeNull()
        ->and($belumMulaiAttempt->waktu_mulai)->toBeNull();
});
