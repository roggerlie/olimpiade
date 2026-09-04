<?php

use App\Models\BankSoal;
use App\Models\Jenjang;
use App\Models\Pelajaran;
use App\Models\Ujian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('it lists existing bank soal with jenjang and pelajaran', function () {
    actingAsAdmin();
    $jenjang = Jenjang::factory()->create(['nama' => 'Sekolah Dasar']);
    $pelajaran = Pelajaran::factory()->create(['nama' => 'Matematika']);
    BankSoal::factory()->create([
        'jenjang_id' => $jenjang->id,
        'pelajaran_id' => $pelajaran->id,
        'nama' => 'Bank Matematika SD',
    ]);

    Livewire::test('admin.bank-soal.manager')
        ->assertSee('Bank Matematika SD')
        ->assertSee('Sekolah Dasar')
        ->assertSee('Matematika');
});

test('it validates required fields before creating', function () {
    actingAsAdmin();

    Livewire::test('admin.bank-soal.manager')
        ->call('create')
        ->call('save')
        ->assertHasErrors(['jenjangId' => 'required', 'pelajaranId' => 'required', 'nama' => 'required']);

    expect(BankSoal::count())->toBe(0);
});

test('it creates a new bank soal', function () {
    actingAsAdmin();
    $jenjang = Jenjang::factory()->create();
    $pelajaran = Pelajaran::factory()->create();

    Livewire::test('admin.bank-soal.manager')
        ->call('create')
        ->set('jenjangId', $jenjang->id)
        ->set('pelajaranId', $pelajaran->id)
        ->set('nama', 'Bank Soal Baru')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showModal', false);

    expect(BankSoal::where('nama', 'Bank Soal Baru')->exists())->toBeTrue();
});

test('it prefills and updates an existing bank soal', function () {
    actingAsAdmin();
    $bankSoal = BankSoal::factory()->create(['nama' => 'Lama']);

    Livewire::test('admin.bank-soal.manager')
        ->call('edit', $bankSoal->id)
        ->assertSet('nama', 'Lama')
        ->set('nama', 'Baru')
        ->call('save')
        ->assertHasNoErrors();

    expect($bankSoal->fresh()->nama)->toBe('Baru');
});

test('it deletes an unused bank soal', function () {
    actingAsAdmin();
    $bankSoal = BankSoal::factory()->create();

    Livewire::test('admin.bank-soal.manager')->call('delete', $bankSoal->id);

    expect(BankSoal::find($bankSoal->id))->toBeNull();
});

test('it refuses to delete a bank soal still referenced by an ujian', function () {
    actingAsAdmin();
    $bankSoal = BankSoal::factory()->create();
    Ujian::factory()->create([
        'bank_soal_id' => $bankSoal->id,
        'jenjang_id' => $bankSoal->jenjang_id,
        'pelajaran_id' => $bankSoal->pelajaran_id,
    ]);

    Livewire::test('admin.bank-soal.manager')
        ->call('delete', $bankSoal->id)
        ->assertSet('errorMessage', fn ($message) => str_contains($message, 'tidak bisa dihapus'));

    expect(BankSoal::find($bankSoal->id))->not->toBeNull();
});
