<?php

use App\Models\BankSoal;
use App\Models\SiswaUjian;
use App\Models\Soal;
use App\Models\Ujian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function basePayload(BankSoal $bankSoal): array
{
    return [
        'bankSoalId' => $bankSoal->id,
        'nama' => 'Ujian Matematika',
        'jumlahSoal' => 2,
        'sesiMulai' => now()->addDay()->format('Y-m-d\TH:i'),
        'sesiSelesai' => now()->addDay()->addHours(2)->format('Y-m-d\TH:i'),
    ];
}

test('it validates required fields before creating', function () {
    actingAsAdmin();

    Livewire::test('admin.ujian.manager')
        ->call('create')
        ->call('save')
        ->assertHasErrors(['bankSoalId' => 'required', 'nama' => 'required', 'jumlahSoal' => 'required', 'sesiMulai' => 'required', 'sesiSelesai' => 'required']);

    expect(Ujian::count())->toBe(0);
});

test('it creates a new ujian and auto-computes duration from sesi window', function () {
    actingAsAdmin();
    $bankSoal = BankSoal::factory()->create();
    Soal::factory()->count(3)->create(['bank_soal_id' => $bankSoal->id]);

    Livewire::test('admin.ujian.manager')
        ->call('create')
        ->set(basePayload($bankSoal))
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showModal', false);

    $ujian = Ujian::where('nama', 'Ujian Matematika')->first();
    expect($ujian)->not->toBeNull()
        ->and($ujian->bank_soal_id)->toBe($bankSoal->id)
        ->and($ujian->jenjang_id)->toBe($bankSoal->jenjang_id)
        ->and($ujian->pelajaran_id)->toBe($bankSoal->pelajaran_id)
        ->and($ujian->durasi_detik)->toBe(7200);
});

test('it rejects sesi selesai before sesi mulai', function () {
    actingAsAdmin();
    $bankSoal = BankSoal::factory()->create();
    Soal::factory()->count(3)->create(['bank_soal_id' => $bankSoal->id]);

    Livewire::test('admin.ujian.manager')
        ->call('create')
        ->set(array_merge(basePayload($bankSoal), [
            'sesiMulai' => now()->addDay()->addHours(2)->format('Y-m-d\TH:i'),
            'sesiSelesai' => now()->addDay()->format('Y-m-d\TH:i'),
        ]))
        ->call('save')
        ->assertHasErrors(['sesiSelesai']);

    expect(Ujian::count())->toBe(0);
});

test('it rejects jumlah soal greater than the soal available in the bank', function () {
    actingAsAdmin();
    $bankSoal = BankSoal::factory()->create();
    Soal::factory()->count(2)->create(['bank_soal_id' => $bankSoal->id]);

    Livewire::test('admin.ujian.manager')
        ->call('create')
        ->set(array_merge(basePayload($bankSoal), ['jumlahSoal' => 5]))
        ->call('save')
        ->assertHasErrors(['jumlahSoal']);

    expect(Ujian::count())->toBe(0);
});

test('it prefills and updates an existing ujian', function () {
    actingAsAdmin();
    $bankSoal = BankSoal::factory()->create();
    Soal::factory()->count(3)->create(['bank_soal_id' => $bankSoal->id]);
    $ujian = Ujian::factory()->create([
        'bank_soal_id' => $bankSoal->id,
        'jenjang_id' => $bankSoal->jenjang_id,
        'pelajaran_id' => $bankSoal->pelajaran_id,
        'nama' => 'Lama',
        'jumlah_soal' => 2,
    ]);

    Livewire::test('admin.ujian.manager')
        ->call('edit', $ujian->id)
        ->assertSet('nama', 'Lama')
        ->set('nama', 'Baru')
        ->call('save')
        ->assertHasNoErrors();

    expect($ujian->fresh()->nama)->toBe('Baru');
});

test('it deletes an unused ujian', function () {
    actingAsAdmin();
    $ujian = Ujian::factory()->create();

    Livewire::test('admin.ujian.manager')->call('delete', $ujian->id);

    expect(Ujian::find($ujian->id))->toBeNull();
});

test('it refuses to delete an ujian already registered by a siswa ujian', function () {
    actingAsAdmin();
    $ujian = Ujian::factory()->create();
    SiswaUjian::factory()->create(['ujian_id' => $ujian->id]);

    Livewire::test('admin.ujian.manager')
        ->call('delete', $ujian->id)
        ->assertSet('errorMessage', fn ($message) => str_contains($message, 'tidak bisa dihapus'));

    expect(Ujian::find($ujian->id))->not->toBeNull();
});
