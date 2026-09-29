<?php

use App\Models\BankSoal;
use App\Models\Soal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// This file only exercises the Livewire component's plumbing (modal
// open/close, "file required"/mime validation, permanent storage of the
// upload). The actual row-parsing business logic is covered directly against
// App\Imports\SoalImport in tests/Feature/SoalImportTest.php — Livewire's
// file-upload test helper only accepts its own fake files, which can carry a
// plain CSV (see the storage test below) but not real spreadsheet bytes.

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

test('it keeps the uploaded file permanently under a linux-safe name and imports from it', function () {
    Storage::fake('local');
    actingAsAdmin();
    $bankSoal = BankSoal::factory()->create();

    $csv = "pertanyaan,pilihan_a,pilihan_b,pilihan_c,pilihan_d,pilihan_e,jawaban\n"
        ."Berapa 2 + 2?,3,4,5,6,,B\n";

    Livewire::test('admin.soal.import', ['bankSoalId' => $bankSoal->id])
        ->set('file', UploadedFile::fake()->createWithContent('Data Soal FINAL (1).CSV', $csv))
        ->call('import')
        ->assertHasNoErrors()
        ->assertSet('imported', 1);

    $stored = Storage::disk('local')->files("imports/soal/{$bankSoal->id}");

    expect($stored)->toHaveCount(1)
        ->and($stored[0])->toMatch('#^imports/soal/'.$bankSoal->id.'/\d{8}_\d{6}_[a-z0-9]{6}_data-soal-final-1\.csv$#')
        ->and(Storage::disk('local')->get($stored[0]))->toBe($csv)
        ->and(Soal::where('bank_soal_id', $bankSoal->id)->count())->toBe(1);
});
