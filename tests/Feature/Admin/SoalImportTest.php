<?php

use App\Models\BankSoal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// This file only exercises the Livewire component's plumbing (modal
// open/close, "file required"/mime validation). The actual row-parsing
// business logic is covered directly against App\Imports\SoalImport in
// tests/Feature/SoalImportTest.php — Livewire's file-upload test helper
// only accepts its own fake files, which can't carry real spreadsheet bytes.

test('the open-soal-import-modal event opens the modal', function () {
    actingAsAdmin();
    $bankSoal = BankSoal::factory()->create();

    Livewire::test('admin.soal.import', ['bankSoalId' => $bankSoal->id])
        ->assertSet('showModal', false)
        ->dispatch('open-soal-import-modal')
        ->assertSet('showModal', true);
});

test('closeModal hides the modal', function () {
    actingAsAdmin();
    $bankSoal = BankSoal::factory()->create();

    Livewire::test('admin.soal.import', ['bankSoalId' => $bankSoal->id])
        ->dispatch('open-soal-import-modal')
        ->call('closeModal')
        ->assertSet('showModal', false);
});

test('it requires a file before importing', function () {
    actingAsAdmin();
    $bankSoal = BankSoal::factory()->create();

    Livewire::test('admin.soal.import', ['bankSoalId' => $bankSoal->id])
        ->call('import')
        ->assertHasErrors(['file' => 'required']);
});

test('it rejects a file with the wrong mime type', function () {
    actingAsAdmin();
    $bankSoal = BankSoal::factory()->create();

    Livewire::test('admin.soal.import', ['bankSoalId' => $bankSoal->id])
        ->set('file', UploadedFile::fake()->create('soal.pdf', 10, 'application/pdf'))
        ->call('import')
        ->assertHasErrors(['file' => 'mimes']);
});

test('the dropzone shows the selected filename, and it clears when the modal reopens', function () {
    actingAsAdmin();
    $bankSoal = BankSoal::factory()->create();

    $component = Livewire::test('admin.soal.import', ['bankSoalId' => $bankSoal->id])
        ->set('file', UploadedFile::fake()->create('data-soal.xlsx', 10));

    expect($component->instance()->fileName())->toBe('data-soal.xlsx');

    $component->dispatch('open-soal-import-modal');

    expect($component->instance()->fileName())->toBeNull();
});
