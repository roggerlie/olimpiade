<?php

use App\Models\Jenjang;
use App\Models\Peserta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// This file only exercises the Livewire component's plumbing (modal
// open/close, "file required"/mime validation, permanent storage of the
// upload). The actual row-parsing business logic is covered directly against
// App\Imports\PesertaImport in tests/Feature/PesertaImportTest.php — Livewire's
// file-upload test helper only accepts its own fake files, which can carry a
// plain CSV (see the storage test below) but not real spreadsheet bytes.

test('the open-import-modal event opens the modal', function () {
    actingAsAdmin();

    Livewire::test('admin.peserta.import')
        ->assertSet('showModal', false)
        ->dispatch('open-import-modal')
        ->assertSet('showModal', true);
});

test('closeModal hides the modal', function () {
    actingAsAdmin();

    Livewire::test('admin.peserta.import')
        ->dispatch('open-import-modal')
        ->call('closeModal')
        ->assertSet('showModal', false);
});

test('it requires a file before importing', function () {
    actingAsAdmin();

    Livewire::test('admin.peserta.import')
        ->call('import')
        ->assertHasErrors(['file' => 'required']);
});

test('it rejects a file with the wrong mime type', function () {
    actingAsAdmin();

    Livewire::test('admin.peserta.import')
        ->set('file', UploadedFile::fake()->create('peserta.pdf', 10, 'application/pdf'))
        ->call('import')
        ->assertHasErrors(['file' => 'mimes']);
});

test('the dropzone shows the selected filename, and it clears when the modal reopens', function () {
    actingAsAdmin();

    $component = Livewire::test('admin.peserta.import')
        ->set('file', UploadedFile::fake()->create('data-peserta.xlsx', 10));

    expect($component->instance()->fileName())->toBe('data-peserta.xlsx');

    $component->dispatch('open-import-modal');

    expect($component->instance()->fileName())->toBeNull();
});

test('it keeps the uploaded file permanently under a linux-safe name and imports from it', function () {
    Storage::fake('local');
    actingAsAdmin();
    Jenjang::factory()->create(['nama' => 'SD']);

    $csv = "noreg,nama,jenjang,asal_sekolah,password\n"
        ."1000000001,Budi Santoso,SD,SD Contoh,\n";

    Livewire::test('admin.peserta.import')
        ->set('file', UploadedFile::fake()->createWithContent('Data Peserta KELAS 6.CSV', $csv))
        ->call('import')
        ->assertHasNoErrors()
        ->assertSet('imported', 1);

    $stored = Storage::disk('local')->files('imports/peserta');

    expect($stored)->toHaveCount(1)
        ->and($stored[0])->toMatch('#^imports/peserta/\d{8}_\d{6}_[a-z0-9]{6}_data-peserta-kelas-6\.csv$#')
        ->and(Storage::disk('local')->get($stored[0]))->toBe($csv)
        ->and(Peserta::where('noreg', '1000000001')->exists())->toBeTrue();
});
