<?php

use App\Models\PesertaUjian;
use App\Models\Ruangan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('the master data page has a ruangan tab listing existing ruangan', function () {
    actingAsAdmin();
    Ruangan::factory()->create(['nama' => 'Lab Komputer 1', 'kapasitas' => 32, 'keterangan' => 'Gedung B']);

    $this->get(route('admin.master'))
        ->assertOk()
        ->assertSee('Lab Komputer 1')
        ->assertSee('32 peserta')
        ->assertSee('Gedung B');
});

test('it validates required fields, positive kapasitas and a unique nama', function () {
    actingAsAdmin();
    Ruangan::factory()->create(['nama' => 'Lab 1']);

    Livewire::test('admin.ruangan.manager')
        ->call('create')
        ->call('save')
        ->assertHasErrors(['nama' => 'required', 'kapasitas' => 'required'])
        ->set('nama', 'Lab 1')
        ->set('kapasitas', 0)
        ->call('save')
        ->assertHasErrors(['nama' => 'unique', 'kapasitas' => 'min']);

    expect(Ruangan::count())->toBe(1);
});

test('it creates a ruangan', function () {
    actingAsAdmin();

    Livewire::test('admin.ruangan.manager')
        ->call('create')
        ->set('nama', 'Lab Komputer 1')
        ->set('kapasitas', 30)
        ->set('keterangan', 'Gedung B lantai 2')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showModal', false);

    expect(Ruangan::where('nama', 'Lab Komputer 1')->first())
        ->kapasitas->toBe(30)
        ->keterangan->toBe('Gedung B lantai 2');
});

test('it prefills and updates a ruangan, keeping its own nama allowed', function () {
    actingAsAdmin();
    $ruangan = Ruangan::factory()->create(['nama' => 'Lab 1', 'kapasitas' => 30]);

    Livewire::test('admin.ruangan.manager')
        ->call('edit', $ruangan->id)
        ->assertSet('nama', 'Lab 1')
        ->assertSet('kapasitas', 30)
        ->set('kapasitas', 40)
        ->call('save')
        ->assertHasNoErrors();

    expect($ruangan->fresh()->kapasitas)->toBe(40);
});

test('it deletes an unused ruangan', function () {
    actingAsAdmin();
    $ruangan = Ruangan::factory()->create();

    Livewire::test('admin.ruangan.manager')->call('delete', $ruangan->id);

    expect(Ruangan::find($ruangan->id))->toBeNull();
});

test('it refuses to delete a ruangan peserta are still placed in', function () {
    actingAsAdmin();
    $ruangan = Ruangan::factory()->create();
    PesertaUjian::factory()->create(['ruangan_id' => $ruangan->id]);

    Livewire::test('admin.ruangan.manager')
        ->call('delete', $ruangan->id)
        ->assertSet('errorMessage', fn ($message) => str_contains($message, 'tidak bisa dihapus'));

    expect(Ruangan::find($ruangan->id))->not->toBeNull();
});

test('an operator cannot manage ruangan', function () {
    actingAsAdminRole('operator');

    Livewire::test('admin.ruangan.manager')
        ->call('create')
        ->assertForbidden();
});
