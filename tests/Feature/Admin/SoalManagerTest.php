<?php

use App\Models\BankSoal;
use App\Models\Soal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// Tambah/Ubah Soal moved off this component's own modal onto their own page
// (see admin.soal.form / tests/Feature/Admin/SoalFormTest.php) — this file
// now only covers what's actually left on admin.soal.manager: list, search,
// and delete.

test('it lists soal belonging to the given bank soal only', function () {
    actingAsAdmin();
    $bankSoal = BankSoal::factory()->create();
    $other = BankSoal::factory()->create();
    Soal::factory()->create(['bank_soal_id' => $bankSoal->id, 'pertanyaan' => 'Soal milik bank ini']);
    Soal::factory()->create(['bank_soal_id' => $other->id, 'pertanyaan' => 'Soal bank lain']);

    Livewire::test('admin.soal.manager', ['bankSoalId' => $bankSoal->id])
        ->assertSee('Soal milik bank ini')
        ->assertDontSee('Soal bank lain');
});

test('search filters soal by pertanyaan text within the same bank soal', function () {
    actingAsAdmin();
    $bankSoal = BankSoal::factory()->create();
    Soal::factory()->create(['bank_soal_id' => $bankSoal->id, 'pertanyaan' => 'Berapa hasil dari 2 + 2?']);
    Soal::factory()->create(['bank_soal_id' => $bankSoal->id, 'pertanyaan' => 'Ibu kota Indonesia adalah?']);

    Livewire::test('admin.soal.manager', ['bankSoalId' => $bankSoal->id])
        ->set('search', 'ibu kota')
        ->assertSee('Ibu kota Indonesia adalah?')
        ->assertDontSee('Berapa hasil dari 2 + 2?');
});

test('totalSoal always reflects the bank soal\'s real count, unaffected by search', function () {
    actingAsAdmin();
    $bankSoal = BankSoal::factory()->create();
    Soal::factory()->count(3)->create(['bank_soal_id' => $bankSoal->id]);

    Livewire::test('admin.soal.manager', ['bankSoalId' => $bankSoal->id])
        ->assertSet('totalSoal', 3)
        ->set('search', 'nonexistent-query')
        ->assertSet('totalSoal', 3);
});

test('it deletes a soal', function () {
    actingAsAdmin();
    $bankSoal = BankSoal::factory()->create();
    $soal = Soal::factory()->create(['bank_soal_id' => $bankSoal->id]);

    Livewire::test('admin.soal.manager', ['bankSoalId' => $bankSoal->id])->call('delete', $soal->id);

    expect(Soal::find($soal->id))->toBeNull();
});

test('a just-saved status message from the form page is picked up via session flash', function () {
    actingAsAdmin();
    $bankSoal = BankSoal::factory()->create();

    session()->flash('status', 'Soal ditambahkan.');

    Livewire::test('admin.soal.manager', ['bankSoalId' => $bankSoal->id])
        ->assertSee('Soal ditambahkan.');
});
