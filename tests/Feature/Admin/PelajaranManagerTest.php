<?php

use App\Models\BankSoal;
use App\Models\Jenjang;
use App\Models\Pelajaran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('it lists existing pelajaran', function () {
    actingAsAdmin();
    Pelajaran::factory()->create(['nama' => 'Matematika']);

    Livewire::test('admin.pelajaran.manager')->assertSee('Matematika');
});

test('it validates required fields before creating', function () {
    actingAsAdmin();

    Livewire::test('admin.pelajaran.manager')
        ->call('create')
        ->set('nama', '')
        ->call('save')
        ->assertHasErrors(['nama' => 'required']);

    expect(Pelajaran::count())->toBe(0);
});

test('it creates a new pelajaran', function () {
    actingAsAdmin();

    Livewire::test('admin.pelajaran.manager')
        ->call('create')
        ->set('nama', 'Matematika')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showModal', false);

    expect(Pelajaran::where('nama', 'Matematika')->exists())->toBeTrue();
});

test('it prefills and updates an existing pelajaran', function () {
    actingAsAdmin();
    $pelajaran = Pelajaran::factory()->create(['nama' => 'Matematika']);

    Livewire::test('admin.pelajaran.manager')
        ->call('edit', $pelajaran->id)
        ->assertSet('nama', 'Matematika')
        ->set('nama', 'Matematika Lanjutan')
        ->call('save')
        ->assertHasNoErrors();

    expect($pelajaran->fresh()->nama)->toBe('Matematika Lanjutan');
});

test('it deletes an unused pelajaran', function () {
    actingAsAdmin();
    $pelajaran = Pelajaran::factory()->create();

    Livewire::test('admin.pelajaran.manager')->call('delete', $pelajaran->id);

    expect(Pelajaran::find($pelajaran->id))->toBeNull();
});

test('it refuses to delete a pelajaran still referenced by a bank soal', function () {
    actingAsAdmin();
    $pelajaran = Pelajaran::factory()->create();
    BankSoal::factory()->create(['pelajaran_id' => $pelajaran->id, 'jenjang_id' => Jenjang::factory()]);

    Livewire::test('admin.pelajaran.manager')
        ->call('delete', $pelajaran->id)
        ->assertSet('errorMessage', fn ($message) => str_contains($message, 'tidak bisa dihapus'));

    expect(Pelajaran::find($pelajaran->id))->not->toBeNull();
});
