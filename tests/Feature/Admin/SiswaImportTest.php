<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// This file only exercises the Livewire component's plumbing (modal
// open/close, "file required"/mime validation). The actual row-parsing
// business logic is covered directly against App\Imports\SiswaImport in
// tests/Feature/SiswaImportTest.php — Livewire's file-upload test helper
// only accepts its own fake files, which can't carry real spreadsheet bytes.

test('the open-import-modal event opens the modal', function () {
    actingAsAdmin();

    Livewire::test('admin.siswa.import')
        ->assertSet('showModal', false)
        ->dispatch('open-import-modal')
        ->assertSet('showModal', true);
});

test('closeModal hides the modal', function () {
    actingAsAdmin();

    Livewire::test('admin.siswa.import')
        ->dispatch('open-import-modal')
        ->call('closeModal')
        ->assertSet('showModal', false);
});

test('it requires a file before importing', function () {
    actingAsAdmin();

    Livewire::test('admin.siswa.import')
        ->call('import')
        ->assertHasErrors(['file' => 'required']);
});

test('it rejects a file with the wrong mime type', function () {
    actingAsAdmin();

    Livewire::test('admin.siswa.import')
        ->set('file', UploadedFile::fake()->create('peserta.pdf', 10, 'application/pdf'))
        ->call('import')
        ->assertHasErrors(['file' => 'mimes']);
});
