<?php

use App\Models\Jenjang;
use App\Models\Siswa;
use App\Models\SiswaSoal;
use App\Models\SiswaUjian;
use App\Models\Ujian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function ujianForJenjang(Jenjang $jenjang): Ujian
{
    return Ujian::factory()->create(['jenjang_id' => $jenjang->id]);
}

test('it only lists siswa belonging to the ujian jenjang', function () {
    actingAsAdmin();
    $jenjangA = Jenjang::factory()->create();
    $jenjangB = Jenjang::factory()->create();
    $ujian = ujianForJenjang($jenjangA);
    Siswa::factory()->create(['jenjang_id' => $jenjangA->id, 'nama' => 'Peserta Jenjang A']);
    Siswa::factory()->create(['jenjang_id' => $jenjangB->id, 'nama' => 'Peserta Jenjang B']);

    Livewire::test('admin.siswa-ujian.manager', ['ujianId' => $ujian->id])
        ->assertSee('Peserta Jenjang A')
        ->assertDontSee('Peserta Jenjang B');
});

test('it registers a single unregistered siswa', function () {
    actingAsAdmin();
    $jenjang = Jenjang::factory()->create();
    $ujian = ujianForJenjang($jenjang);
    $siswa = Siswa::factory()->create(['jenjang_id' => $jenjang->id]);

    Livewire::test('admin.siswa-ujian.manager', ['ujianId' => $ujian->id])
        ->call('toggleDaftar', $siswa->id);

    expect(SiswaUjian::where('siswa_id', $siswa->id)->where('ujian_id', $ujian->id)->exists())->toBeTrue();
});

test('it unregisters a siswa who has not started the exam', function () {
    actingAsAdmin();
    $jenjang = Jenjang::factory()->create();
    $ujian = ujianForJenjang($jenjang);
    $siswa = Siswa::factory()->create(['jenjang_id' => $jenjang->id]);
    SiswaUjian::factory()->create(['siswa_id' => $siswa->id, 'ujian_id' => $ujian->id]);

    Livewire::test('admin.siswa-ujian.manager', ['ujianId' => $ujian->id])
        ->call('toggleDaftar', $siswa->id);

    expect(SiswaUjian::where('siswa_id', $siswa->id)->where('ujian_id', $ujian->id)->exists())->toBeFalse();
});

test('it refuses to unregister a siswa who already started the exam', function () {
    actingAsAdmin();
    $jenjang = Jenjang::factory()->create();
    $ujian = ujianForJenjang($jenjang);
    $siswa = Siswa::factory()->create(['jenjang_id' => $jenjang->id]);
    SiswaUjian::factory()->create(['siswa_id' => $siswa->id, 'ujian_id' => $ujian->id, 'waktu_mulai' => now()]);

    Livewire::test('admin.siswa-ujian.manager', ['ujianId' => $ujian->id])
        ->call('toggleDaftar', $siswa->id)
        ->assertSet('errorMessage', fn ($message) => str_contains($message, 'sudah mulai mengerjakan'));

    expect(SiswaUjian::where('siswa_id', $siswa->id)->where('ujian_id', $ujian->id)->exists())->toBeTrue();
});

test('daftarkanSemua registers every unregistered siswa of the jenjang only', function () {
    actingAsAdmin();
    $jenjang = Jenjang::factory()->create();
    $otherJenjang = Jenjang::factory()->create();
    $ujian = ujianForJenjang($jenjang);

    $sudahTerdaftar = Siswa::factory()->create(['jenjang_id' => $jenjang->id]);
    SiswaUjian::factory()->create(['siswa_id' => $sudahTerdaftar->id, 'ujian_id' => $ujian->id]);

    $belumTerdaftar = Siswa::factory()->count(2)->create(['jenjang_id' => $jenjang->id]);
    Siswa::factory()->create(['jenjang_id' => $otherJenjang->id]);

    Livewire::test('admin.siswa-ujian.manager', ['ujianId' => $ujian->id])
        ->call('daftarkanSemua')
        ->assertSet('statusMessage', fn ($message) => str_contains($message, '2 peserta baru'));

    expect(SiswaUjian::where('ujian_id', $ujian->id)->count())->toBe(3);
    foreach ($belumTerdaftar as $siswa) {
        expect(SiswaUjian::where('siswa_id', $siswa->id)->where('ujian_id', $ujian->id)->exists())->toBeTrue();
    }
});

test('daftarkanSemua is idempotent when everyone is already registered', function () {
    actingAsAdmin();
    $jenjang = Jenjang::factory()->create();
    $ujian = ujianForJenjang($jenjang);
    $siswa = Siswa::factory()->create(['jenjang_id' => $jenjang->id]);
    SiswaUjian::factory()->create(['siswa_id' => $siswa->id, 'ujian_id' => $ujian->id]);

    Livewire::test('admin.siswa-ujian.manager', ['ujianId' => $ujian->id])
        ->call('daftarkanSemua')
        ->assertSet('statusMessage', fn ($message) => str_contains($message, '0 peserta baru'));

    expect(SiswaUjian::where('ujian_id', $ujian->id)->count())->toBe(1);
});

test('resetProgres wipes timing, score and answers but keeps the registration', function () {
    actingAsAdmin();
    $jenjang = Jenjang::factory()->create();
    $ujian = ujianForJenjang($jenjang);
    $siswa = Siswa::factory()->create(['jenjang_id' => $jenjang->id]);
    $siswaUjian = SiswaUjian::factory()->selesai()->create(['siswa_id' => $siswa->id, 'ujian_id' => $ujian->id]);
    SiswaSoal::factory()->count(3)->create(['siswa_ujian_id' => $siswaUjian->id]);

    Livewire::test('admin.siswa-ujian.manager', ['ujianId' => $ujian->id])
        ->call('resetProgres', $siswa->id)
        ->assertSet('statusMessage', fn ($message) => str_contains($message, 'direset'));

    $siswaUjian->refresh();
    expect($siswaUjian->waktu_mulai)->toBeNull()
        ->and($siswaUjian->waktu_selesai)->toBeNull()
        ->and($siswaUjian->benar)->toBeNull()
        ->and($siswaUjian->salah)->toBeNull()
        ->and($siswaUjian->nilai)->toBeNull()
        ->and(SiswaSoal::where('siswa_ujian_id', $siswaUjian->id)->count())->toBe(0)
        // still registered — resetProgres only clears progress, not the enrolment.
        ->and(SiswaUjian::where('id', $siswaUjian->id)->exists())->toBeTrue();
});

test('resetSemua only touches attempts that have actually started', function () {
    actingAsAdmin();
    $jenjang = Jenjang::factory()->create();
    $ujian = ujianForJenjang($jenjang);

    $sudahMulai = Siswa::factory()->create(['jenjang_id' => $jenjang->id]);
    $attemptMulai = SiswaUjian::factory()->selesai()->create(['siswa_id' => $sudahMulai->id, 'ujian_id' => $ujian->id]);
    SiswaSoal::factory()->count(2)->create(['siswa_ujian_id' => $attemptMulai->id]);

    $belumMulai = Siswa::factory()->create(['jenjang_id' => $jenjang->id]);
    SiswaUjian::factory()->create(['siswa_id' => $belumMulai->id, 'ujian_id' => $ujian->id]);

    Livewire::test('admin.siswa-ujian.manager', ['ujianId' => $ujian->id])
        ->call('resetSemua')
        ->assertSet('statusMessage', fn ($message) => str_contains($message, '1 progres'));

    expect($attemptMulai->fresh()->waktu_mulai)->toBeNull()
        ->and(SiswaSoal::where('siswa_ujian_id', $attemptMulai->id)->count())->toBe(0);

    $belumMulaiAttempt = SiswaUjian::where('siswa_id', $belumMulai->id)->first();
    expect($belumMulaiAttempt)->not->toBeNull()
        ->and($belumMulaiAttempt->waktu_mulai)->toBeNull();
});
